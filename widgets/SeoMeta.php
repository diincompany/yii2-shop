<?php

namespace DiinCompany\Yii2Shop\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\Url;

/**
 * SeoMeta widget — centralizes all SEO metadata (meta tags, Open Graph, Twitter
 * Cards, canonical URL, robots, and JSON-LD structured data) for the shop module.
 *
 * Usage in views:
 *
 *   // Product detail page
 *   SeoMeta::widget([
 *       'type'        => SeoMeta::TYPE_PRODUCT,
 *       'title'       => $product['name'],
 *       'description' => $product['short_description'] ?? '',
 *       'image'       => $mainImageUrl,
 *       'product'     => $product,
 *       'price'       => $selectedPriceAmount,
 *       'salePrice'   => $selectedSalePriceAmount,
 *       'inStock'     => $isProductAvailable,
 *       'breadcrumbs' => $this->params['breadcrumbs'],
 *   ]);
 *
 *   // Category / listing page
 *   SeoMeta::widget([
 *       'type'        => SeoMeta::TYPE_CATEGORY,
 *       'title'       => $category['name'],
 *       'description' => $category['description'] ?? '',
 *       'image'       => $category['image'] ?? '',
 *       'category'    => $category,
 *       'products'    => $products,
 *       'breadcrumbs' => $this->params['breadcrumbs'],
 *   ]);
 *
 *   // Home / generic page
 *   SeoMeta::widget(['type' => SeoMeta::TYPE_HOME]);
 *
 *   // Cart / checkout (auto-noindex)
 *   SeoMeta::widget(['type' => SeoMeta::TYPE_CART]);
 */
class SeoMeta extends Widget
{
    // --- Page type constants ---
    const TYPE_HOME         = 'home';
    const TYPE_PRODUCT      = 'product';
    const TYPE_CATEGORY     = 'category';
    const TYPE_LISTING      = 'listing';
    const TYPE_CART         = 'cart';
    const TYPE_CHECKOUT     = 'checkout';
    const TYPE_CONFIRMATION = 'confirmation';
    const TYPE_DEFAULT      = 'default';

    /** @var string Page type. Determines which JSON-LD schemas are emitted. */
    public string $type = self::TYPE_DEFAULT;

    /** @var string|null Page title (falls back to $this->view->title). */
    public ?string $title = null;

    /** @var string|null Meta description (auto-truncated to 160 chars). */
    public ?string $description = null;

    /** @var string|null Canonical/OG/Twitter image URL. */
    public ?string $image = null;

    /** @var string|null Canonical URL. Auto-detected from current request when null. */
    public ?string $url = null;

    /** @var string|array Meta keywords — string or array of strings. */
    public string|array $keywords = '';

    /** @var bool Force noindex/nofollow. Auto-enabled for cart/checkout/confirmation. */
    public bool $noindex = false;

    // --- Product-specific properties ---

    /** @var array Full product data array (from API). Required for TYPE_PRODUCT. */
    public array $product = [];

    /** @var string Currency code for price metadata. */
    public string $currency = 'HNL';

    /** @var float Base/regular price. */
    public float $price = 0.0;

    /** @var float|null Sale price. Use 0 or null when there is no active sale. */
    public ?float $salePrice = null;

    /** @var bool Whether the product (or selected variant) is in stock. */
    public bool $inStock = true;

    // --- Listing/category properties ---

    /** @var array|null Category data array (from API). */
    public ?array $category = null;

    /**
     * @var array List of product arrays for ItemList JSON-LD on listing/category pages.
     *            Each item must have at least 'slug' and 'name'.
     */
    public array $products = [];

    // --- Shared ---

    /**
     * @var array Breadcrumb items for BreadcrumbList JSON-LD.
     *            Mirrors the format used in $this->params['breadcrumbs']:
     *            [ ['label' => 'Shop', 'url' => ['/shop']], 'Product Name' ]
     */
    public array $breadcrumbs = [];

    // --- Internal ---
    private const MAX_DESCRIPTION = 160;
    private const MAX_TITLE       = 60;

    public function init(): void
    {
        parent::init();

        if ($this->url === null) {
            $this->url = Url::current([], true);
        }

        // Auto-noindex pages that should never be indexed.
        if (in_array($this->type, [self::TYPE_CART, self::TYPE_CHECKOUT, self::TYPE_CONFIRMATION], true)) {
            $this->noindex = true;
        }
    }

    public function run(): string
    {
        $view     = $this->view;
        $siteName = Yii::$app->name;

        $title       = $this->truncate($this->title ?? (string) ($view->title ?? ''), self::MAX_TITLE) ?: $siteName;
        $description = $this->truncate(
            $this->sanitizeText($this->description ?? (string) ($view->params['meta_description'] ?? '')),
            self::MAX_DESCRIPTION
        );
        $image    = $this->image ?: (string) (Yii::$app->params['metaDefaultImage'] ?? '');
        $keywords = is_array($this->keywords) ? implode(', ', $this->keywords) : $this->keywords;

        // Push back so the layout's registerMetaTag calls in main.php also pick them up.
        $view->params['meta_description'] = $description;
        $view->params['meta_keywords']    = $keywords;

        // --- Canonical ---
        $view->registerLinkTag(['rel' => 'canonical', 'href' => $this->url], 'canonical');

        // --- Robots ---
        $robotsContent = $this->noindex ? 'noindex, nofollow' : 'index, follow';
        $view->registerMetaTag(['name' => 'robots', 'content' => $robotsContent], 'robots');

        // --- Open Graph ---
        $ogType = ($this->type === self::TYPE_PRODUCT) ? 'product' : 'website';
        $locale = str_replace('-', '_', Yii::$app->language);

        $view->registerMetaTag(['property' => 'og:type',        'content' => $ogType],    'og:type');
        $view->registerMetaTag(['property' => 'og:title',       'content' => $title],     'og:title');
        $view->registerMetaTag(['property' => 'og:description', 'content' => $description], 'og:description');
        $view->registerMetaTag(['property' => 'og:url',         'content' => $this->url],  'og:url');
        $view->registerMetaTag(['property' => 'og:site_name',   'content' => $siteName],   'og:site_name');
        $view->registerMetaTag(['property' => 'og:locale',      'content' => $locale],     'og:locale');

        if ($image !== '') {
            $view->registerMetaTag(['property' => 'og:image',     'content' => $image], 'og:image');
            $view->registerMetaTag(['property' => 'og:image:alt', 'content' => $title], 'og:image:alt');
        }

        // Product-specific Open Graph / Facebook Catalog tags.
        if ($this->type === self::TYPE_PRODUCT) {
            $effectivePrice = ($this->salePrice !== null && $this->salePrice > 0)
                ? $this->salePrice
                : $this->price;

            $view->registerMetaTag(
                ['property' => 'product:price:amount',   'content' => number_format($effectivePrice, 2, '.', '')],
                'og:price:amount'
            );
            $view->registerMetaTag(
                ['property' => 'product:price:currency', 'content' => $this->currency],
                'og:price:currency'
            );
            $view->registerMetaTag(
                ['property' => 'product:availability', 'content' => $this->inStock ? 'in stock' : 'out of stock'],
                'og:availability'
            );
        }

        // --- Twitter Card ---
        $cardType = ($image !== '') ? 'summary_large_image' : 'summary';
        $view->registerMetaTag(['name' => 'twitter:card',        'content' => $cardType],     'twitter:card');
        $view->registerMetaTag(['name' => 'twitter:title',       'content' => $title],        'twitter:title');
        $view->registerMetaTag(['name' => 'twitter:description', 'content' => $description],  'twitter:description');

        if ($image !== '') {
            $view->registerMetaTag(['name' => 'twitter:image',     'content' => $image], 'twitter:image');
            $view->registerMetaTag(['name' => 'twitter:image:alt', 'content' => $title], 'twitter:image:alt');
        }

        $twitterHandle = (string) (Yii::$app->params['twitterHandle'] ?? '');
        if ($twitterHandle !== '') {
            $view->registerMetaTag(['name' => 'twitter:site', 'content' => $twitterHandle], 'twitter:site');
        }

        // --- JSON-LD Structured Data ---
        return $this->renderJsonLd($title, $description, $image, $siteName);
    }

    // -------------------------------------------------------------------------
    // JSON-LD builders
    // -------------------------------------------------------------------------

    private function renderJsonLd(string $title, string $description, string $image, string $siteName): string
    {
        $schemas = [];

        if ($this->type === self::TYPE_HOME) {
            $schemas[] = $this->buildWebSiteSchema($siteName, $description, $image);
            $schemas[] = $this->buildOrganizationSchema($siteName, $image);
        }

        if ($this->type === self::TYPE_PRODUCT && !empty($this->product)) {
            $schemas[] = $this->buildProductSchema($image);
        }

        if (!empty($this->breadcrumbs)) {
            $schemas[] = $this->buildBreadcrumbSchema();
        }

        if (in_array($this->type, [self::TYPE_LISTING, self::TYPE_CATEGORY], true) && !empty($this->products)) {
            $schemas[] = $this->buildItemListSchema($title, $description);
        }

        $output = '';
        foreach ($schemas as $schema) {
            $encoded = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $output .= '<script type="application/ld+json">' . $encoded . "</script>\n";
        }

        return $output;
    }

    private function buildWebSiteSchema(string $siteName, string $description, string $image): array
    {
        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'url'             => Url::to('/', true),
            'name'            => $siteName,
            'description'     => $description,
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => Url::to(['/shop/products'], true) . '?search={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        if ($image !== '') {
            $schema['image'] = $image;
        }

        return $schema;
    }

    private function buildOrganizationSchema(string $siteName, string $image): array
    {
        $company = Yii::$app->params['company'] ?? [];
        $support = Yii::$app->params['support'] ?? [];

        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'url'      => Url::to('/', true),
            'name'     => $company['legalName'] ?? $siteName,
        ];

        if ($image !== '') {
            $schema['logo'] = $image;
        }

        if (!empty($company['address'])) {
            $schema['address'] = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $company['address'],
                'addressLocality' => $company['city']    ?? '',
                'addressCountry'  => $company['country'] ?? '',
            ];
        }

        $contactEmail = $support['email'] ?? $company['email'] ?? (Yii::$app->params['supportEmail'] ?? '');
        if ($contactEmail !== '') {
            $schema['contactPoint'] = [
                '@type'       => 'ContactPoint',
                'email'       => $contactEmail,
                'contactType' => 'customer service',
                'areaServed'  => $company['country'] ?? 'HN',
            ];
        }

        if (!empty($support['phone'])) {
            $schema['telephone'] = $support['phone'];
        }

        return $schema;
    }

    private function buildProductSchema(string $image): array
    {
        $product        = $this->product;
        $effectivePrice = ($this->salePrice !== null && $this->salePrice > 0)
            ? $this->salePrice
            : $this->price;

        $rawDescription = (string) ($product['short_description'] ?? $product['description'] ?? '');
        $brandName      = (string) ($product['brand']['name'] ?? Yii::$app->params['brandName'] ?? Yii::$app->name);

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => (string) ($product['name'] ?? ''),
            'description' => $this->sanitizeText($rawDescription),
            'sku'         => (string) ($product['code'] ?? $product['sku'] ?? ''),
            'url'         => $this->url,
            'brand'       => [
                '@type' => 'Brand',
                'name'  => $brandName,
            ],
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => $this->url,
                'priceCurrency' => $this->currency,
                'price'         => number_format($effectivePrice, 2, '.', ''),
                'availability'  => $this->inStock
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        if ($image !== '') {
            $schema['image'] = $image;
        }

        // Optional: aggregate rating if available from API.
        $rating = $product['rating'] ?? null;
        if ($rating !== null && (float) $rating > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (string) $rating,
                'reviewCount' => (string) (int) ($product['review_count'] ?? 1),
            ];
        }

        return $schema;
    }

    private function buildBreadcrumbSchema(): array
    {
        $items    = [];
        $position = 1;

        // Home is always position 1.
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => Yii::$app->name,
            'item'     => Url::to('/', true),
        ];

        foreach ($this->breadcrumbs as $crumb) {
            $item = [
                '@type'    => 'ListItem',
                'position' => $position++,
            ];

            if (is_array($crumb)) {
                $item['name'] = (string) ($crumb['label'] ?? '');
                if (!empty($crumb['url'])) {
                    $item['item'] = Url::to($crumb['url'], true);
                }
            } else {
                $item['name'] = (string) $crumb;
            }

            $items[] = $item;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private function buildItemListSchema(string $title, string $description): array
    {
        $items    = [];
        $position = 1;

        foreach ($this->products as $product) {
            $slug = (string) ($product['slug'] ?? '');
            $name = (string) ($product['name'] ?? '');

            if ($slug === '' || $name === '') {
                continue;
            }

            $item = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'url'      => Url::to(['/shop/' . $slug], true),
                'name'     => $name,
            ];

            $itemImage = (string) (
                $product['product_images'][0]['url'] ??
                $product['main_image'] ??
                ''
            );

            if ($itemImage !== '') {
                $item['image'] = $itemImage;
            }

            $itemPrice = (float) ($product['sale_price'] > 0 ? $product['sale_price'] : ($product['price'] ?? 0));
            if ($itemPrice > 0) {
                $item['offers'] = [
                    '@type'         => 'Offer',
                    'price'         => number_format($itemPrice, 2, '.', ''),
                    'priceCurrency' => $this->currency,
                ];
            }

            $items[] = $item;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'name'            => $title,
            'description'     => $description,
            'itemListElement' => $items,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function sanitizeText(string $text): string
    {
        return trim(strip_tags($text));
    }

    private function truncate(string $text, int $maxLength): string
    {
        if (mb_strlen($text, 'UTF-8') <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength - 3, 'UTF-8') . '...';
    }
}
