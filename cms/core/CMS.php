<?php
/**
 * CMS Core Engine — Content Management System for ISNM
 * Central class that manages all CMS operations
 */
require_once __DIR__ . '/ContentRenderer.php';
require_once __DIR__ . '/RBAC.php';
require_once __DIR__ . '/AuditLog.php';
require_once __DIR__ . '/ContentVersioning.php';

class CMS {
    private static $instance = null;
    private $db;
    private $rbac;
    private $audit;
    private $settings = [];
    private $settingsLoaded = false;

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->db = $this->getDb();
        $this->rbac = RBAC::getInstance();
        $this->audit = AuditLog::getInstance();
    }

    private function getDb(): ?mysqli {
        if ($this->db && $this->db->ping()) return $this->db;
        if (function_exists('getWebsiteConnection')) {
            $this->db = getWebsiteConnection();
        }
        if (!$this->db && function_exists('getStudentsConnection')) {
            $this->db = getStudentsConnection();
        }
        return $this->db;
    }

    public function getDbDirect(): ?mysqli {
        return $this->db;
    }

    // ─── Settings ────────────────────────────────────
    public function getSetting(string $key, string $default = ''): string {
        if (!$this->settingsLoaded) $this->loadSettings();
        return $this->settings[$key] ?? $default;
    }

    public function getSettingsByGroup(string $group): array {
        if (!$this->settingsLoaded) $this->loadSettings();
        $result = [];
        foreach ($this->settings as $key => $value) {
            if (strpos($key, $group . ':') === 0) {
                $result[substr($key, strlen($group) + 1)] = $value;
            }
        }
        return $result;
    }

    public function updateSetting(string $key, string $value): bool {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("UPDATE cms_settings SET setting_value = ? WHERE setting_key = ?");
        if (!$stmt) return false;
        $stmt->bind_param('ss', $value, $key);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            $this->settings[$key] = $value;
            $this->audit->log('update', 'setting', 0, $key, null, ['value' => $value]);
        }
        return $result;
    }

    private function loadSettings(): void {
        if (!$this->db) return;
        $result = $this->db->query("SELECT setting_key, setting_value FROM cms_settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        $this->settingsLoaded = true;
    }

    // ─── Pages ───────────────────────────────────────
    public function getPage(string $slug): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM cms_pages WHERE slug = ? AND is_published = 1");
        if (!$stmt) return null;
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    public function getPageById(int $id): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM cms_pages WHERE id = ?");
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    public function getAllPages(bool $includeUnpublished = false): array {
        if (!$this->db) return [];
        $sql = "SELECT * FROM cms_pages" . ($includeUnpublished ? '' : " WHERE is_published = 1") . " ORDER BY sort_order ASC";
        $result = $this->db->query($sql);
        $pages = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $pages[] = $row;
        }
        return $pages;
    }

    public function savePage(array $data, ?int $userId = null): int {
        if (!$this->db) return 0;
        $isNew = empty($data['id']);
        if ($isNew) {
            $stmt = $this->db->prepare("INSERT INTO cms_pages (slug, title, subtitle, page_type, template, hero_title, hero_subtitle, hero_image, hero_overlay_color, content, meta_title, meta_description, og_title, og_description, og_image, canonical_url, schema_type, is_published, is_featured, sort_order, created_by, updated_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        } else {
            $stmt = $this->db->prepare("UPDATE cms_pages SET slug=?, title=?, subtitle=?, page_type=?, template=?, hero_title=?, hero_subtitle=?, hero_image=?, hero_overlay_color=?, content=?, meta_title=?, meta_description=?, og_title=?, og_description=?, og_image=?, canonical_url=?, schema_type=?, is_published=?, is_featured=?, sort_order=?, updated_by=? WHERE id=?");
        }
        if (!$stmt) return 0;

        $slug = $data['slug'] ?? '';
        $title = $data['title'] ?? '';
        $subtitle = $data['subtitle'] ?? '';
        $pageType = $data['page_type'] ?? 'static';
        $template = $data['template'] ?? 'default';
        $heroTitle = $data['hero_title'] ?? '';
        $heroSubtitle = $data['hero_subtitle'] ?? '';
        $heroImage = $data['hero_image'] ?? '';
        $heroOverlay = $data['hero_overlay_color'] ?? 'rgba(26,35,126,0.7)';
        $content = $data['content'] ?? '';
        $metaTitle = $data['meta_title'] ?? '';
        $metaDesc = $data['meta_description'] ?? '';
        $ogTitle = $data['og_title'] ?? '';
        $ogDesc = $data['og_description'] ?? '';
        $ogImage = $data['og_image'] ?? '';
        $canonical = $data['canonical_url'] ?? '';
        $schemaType = $data['schema_type'] ?? '';
        $isPublished = (int)($data['is_published'] ?? 1);
        $isFeatured = (int)($data['is_featured'] ?? 0);
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $userId = $userId ?? ($data['updated_by'] ?? null);

        if ($isNew) {
            $createdBy = $userId;
            $stmt->bind_param('ssssssssssssssssiiiiii', $slug, $title, $subtitle, $pageType, $template, $heroTitle, $heroSubtitle, $heroImage, $heroOverlay, $content, $metaTitle, $metaDesc, $ogTitle, $ogDesc, $ogImage, $canonical, $schemaType, $isPublished, $isFeatured, $sortOrder, $createdBy, $userId);
        } else {
            $id = (int)$data['id'];
            $stmt->bind_param('ssssssssssssssssiiiiii', $slug, $title, $subtitle, $pageType, $template, $heroTitle, $heroSubtitle, $heroImage, $heroOverlay, $content, $metaTitle, $metaDesc, $ogTitle, $ogDesc, $ogImage, $canonical, $schemaType, $isPublished, $isFeatured, $sortOrder, $userId, $id);
        }

        $stmt->execute();
        $insertId = $isNew ? $stmt->insert_id : (int)($data['id'] ?? 0);
        $stmt->close();

        $this->audit->log($isNew ? 'create' : 'update', 'page', $insertId, $title, null, $data);
        ContentVersioning::save('page', $insertId, $title, json_encode($data), $userId);
        return $insertId;
    }

    // ─── Content Blocks ──────────────────────────────
    public function getBlocks(int $pageId): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT * FROM cms_content_blocks WHERE page_id = ? AND is_published = 1 ORDER BY sort_order ASC");
        if (!$stmt) return [];
        $stmt->bind_param('i', $pageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $blocks = [];
        while ($row = $result->fetch_assoc()) $blocks[] = $row;
        $stmt->close();
        return $blocks;
    }

    public function getBlock(int $id): ?array {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM cms_content_blocks WHERE id = ?");
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    public function saveBlock(array $data, ?int $userId = null): int {
        if (!$this->db) return 0;
        $isNew = empty($data['id']);
        if ($isNew) {
            $stmt = $this->db->prepare("INSERT INTO cms_content_blocks (page_id, block_key, block_type, title, subtitle, content, settings, animation, background_style, text_color, sort_order, is_published, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        } else {
            $stmt = $this->db->prepare("UPDATE cms_content_blocks SET page_id=?, block_key=?, block_type=?, title=?, subtitle=?, content=?, settings=?, animation=?, background_style=?, text_color=?, sort_order=?, is_published=? WHERE id=?");
        }
        if (!$stmt) return 0;

        $pageId = (int)($data['page_id'] ?? 0);
        $blockKey = $data['block_key'] ?? '';
        $blockType = $data['block_type'] ?? 'text';
        $title = $data['title'] ?? '';
        $subtitle = $data['subtitle'] ?? '';
        $content = $data['content'] ?? '';
        $settings = $data['settings'] ?? '';
        $animation = $data['animation'] ?? 'fade-up';
        $bgStyle = $data['background_style'] ?? '';
        $textColor = $data['text_color'] ?? '';
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $isPublished = (int)($data['is_published'] ?? 1);

        if ($isNew) {
            $stmt->bind_param('isssssssssiii', $pageId, $blockKey, $blockType, $title, $subtitle, $content, $settings, $animation, $bgStyle, $textColor, $sortOrder, $isPublished, $userId);
        } else {
            $id = (int)$data['id'];
            $stmt->bind_param('isssssssssiii', $pageId, $blockKey, $blockType, $title, $subtitle, $content, $settings, $animation, $bgStyle, $textColor, $sortOrder, $isPublished, $id);
        }

        $stmt->execute();
        $insertId = $isNew ? $stmt->insert_id : (int)($data['id'] ?? 0);
        $stmt->close();

        $this->audit->log($isNew ? 'create' : 'update', 'content_block', $insertId, $title, null, $data);
        return $insertId;
    }

    public function deleteBlock(int $id, ?int $userId = null): bool {
        if (!$this->db) return false;
        $block = $this->getBlock($id);
        if (!$block) return false;
        $stmt = $this->db->prepare("DELETE FROM cms_content_blocks WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        if ($result) {
            $this->audit->log('delete', 'content_block', $id, $block['title'], $block, null);
        }
        return $result;
    }

    // ─── Banners ─────────────────────────────────────
    public function getBanners(string $pageSlug = 'home'): array {
        if (!$this->db) return [];
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("SELECT * FROM cms_banners WHERE page_slug = ? AND is_active = 1 AND (start_date IS NULL OR start_date <= ?) AND (end_date IS NULL OR end_date >= ?) ORDER BY sort_order ASC");
        if (!$stmt) return [];
        $stmt->bind_param('sss', $pageSlug, $now, $now);
        $stmt->execute();
        $result = $stmt->get_result();
        $banners = [];
        while ($row = $result->fetch_assoc()) $banners[] = $row;
        $stmt->close();
        return $banners;
    }

    // ─── Testimonials ────────────────────────────────
    public function getTestimonials(bool $featuredOnly = false): array {
        if (!$this->db) return [];
        $sql = "SELECT * FROM cms_testimonials WHERE is_published = 1" . ($featuredOnly ? " AND is_featured = 1" : "") . " ORDER BY sort_order ASC";
        $result = $this->db->query($sql);
        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $items[] = $row;
        }
        return $items;
    }

    // ─── Events ──────────────────────────────────────
    public function getEvents(int $limit = 10, bool $futureOnly = true): array {
        if (!$this->db) return [];
        $sql = "SELECT * FROM cms_events WHERE is_published = 1";
        if ($futureOnly) $sql .= " AND event_date >= '" . date('Y-m-d') . "'";
        $sql .= " ORDER BY event_date ASC LIMIT " . (int)$limit;
        $result = $this->db->query($sql);
        $events = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $events[] = $row;
        }
        return $events;
    }

    // ─── Gallery ─────────────────────────────────────
    public function getGalleryCategories(): array {
        if (!$this->db) return [];
        $result = $this->db->query("SELECT gc.*, COUNT(gi.id) as image_count FROM cms_gallery_categories gc LEFT JOIN cms_gallery_images gi ON gi.category_id = gc.id WHERE gc.is_active = 1 GROUP BY gc.id ORDER BY gc.sort_order ASC");
        $cats = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $cats[] = $row;
        }
        return $cats;
    }

    public function getGalleryImages(?int $categoryId = null, int $limit = 50): array {
        if (!$this->db) return [];
        $sql = "SELECT * FROM cms_gallery_images WHERE 1=1";
        $params = [];
        $types = '';
        if ($categoryId) {
            $sql .= " AND category_id = ?";
            $params[] = $categoryId;
            $types .= 'i';
        }
        $sql .= " ORDER BY sort_order ASC, created_at DESC LIMIT " . (int)$limit;

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            }
        } else {
            $result = $this->db->query($sql);
        }

        $images = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $images[] = $row;
        }
        return $images;
    }

    // ─── FAQs ────────────────────────────────────────
    public function getFAQs(string $pageSlug = 'general'): array {
        if (!$this->db) return [];
        $stmt = $this->db->prepare("SELECT * FROM cms_faqs WHERE page_slug = ? AND is_published = 1 ORDER BY sort_order ASC");
        if (!$stmt) return [];
        $stmt->bind_param('s', $pageSlug);
        $stmt->execute();
        $result = $stmt->get_result();
        $faqs = [];
        while ($row = $result->fetch_assoc()) $faqs[] = $row;
        $stmt->close();
        return $faqs;
    }

    // ─── Partners ────────────────────────────────────
    public function getPartners(): array {
        if (!$this->db) return [];
        $result = $this->db->query("SELECT * FROM cms_partners WHERE is_active = 1 ORDER BY sort_order ASC");
        $partners = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $partners[] = $row;
        }
        return $partners;
    }

    // ─── News ────────────────────────────────────────
    public function getNews(int $limit = 10, ?string $category = null): array {
        if (!$this->db) return [];
        $sql = "SELECT * FROM news WHERE status = 'published'";
        $params = [];
        $types = '';
        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
            $types .= 's';
        }
        $sql .= " ORDER BY published_at DESC LIMIT " . (int)$limit;

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            }
        } else {
            $result = $this->db->query($sql);
        }

        $articles = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $articles[] = $row;
        }
        return $articles;
    }

    public function getNewsCategories(): array {
        if (!$this->db) return [];
        $result = $this->db->query("SELECT * FROM cms_news_categories WHERE is_active = 1 ORDER BY sort_order ASC");
        $cats = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $cats[] = $row;
        }
        return $cats;
    }

    // ─── Staff Directory ─────────────────────────────
    public function getStaffDirectory(?string $department = null, bool $leadershipOnly = false): array {
        if (!$this->db) return [];
        $sql = "SELECT * FROM cms_staff_directory WHERE is_published = 1";
        $params = [];
        $types = '';
        if ($department) {
            $sql .= " AND department = ?";
            $params[] = $department;
            $types .= 's';
        }
        if ($leadershipOnly) $sql .= " AND is_leadership = 1";
        $sql .= " ORDER BY sort_order ASC, full_name ASC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
            }
        } else {
            $result = $this->db->query($sql);
        }

        $staff = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $staff[] = $row;
        }
        return $staff;
    }

    // ─── Social Links ────────────────────────────────
    public function getSocialLinks(): array {
        if (!$this->db) return [];
        $result = $this->db->query("SELECT * FROM cms_social_links WHERE is_active = 1 ORDER BY sort_order ASC");
        $links = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) $links[] = $row;
        }
        return $links;
    }

    // ─── Page Views Tracking ─────────────────────────
    public function trackPageView(string $slug): void {
        if (!$this->db) return;
        $stmt = $this->db->prepare("INSERT INTO cms_page_views (page_slug, visitor_ip, visitor_agent, referer_url, device_type) VALUES (?,?,?,?,?)");
        if (!$stmt) return;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $device = $this->detectDevice($ua);
        $stmt->bind_param('sssss', $slug, $ip, $ua, $referer, $device);
        $stmt->execute();
        $stmt->close();

        // Update page counter
        $this->db->query("UPDATE cms_pages SET page_views = page_views + 1, last_viewed_at = NOW() WHERE slug = '" . $this->db->real_escape_string($slug) . "'");
    }

    public function getPageStats(): array {
        if (!$this->db) return [];
        $today = date('Y-m-d');
        $result = $this->db->query("SELECT COUNT(*) as total, SUM(CASE WHEN viewed_at >= '$today' THEN 1 ELSE 0 END) as today FROM cms_page_views");
        return $result ? ($result->fetch_assoc() ?: []) : [];
    }

    private function detectDevice(string $ua): string {
        if (preg_match('/mobile|android|iphone|ipad|ipod|blackberry|opera mini/i', $ua)) {
            if (preg_match('/ipad|tablet/i', $ua)) return 'tablet';
            return 'mobile';
        }
        return 'desktop';
    }
}
