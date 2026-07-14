<?php
/**
 * CMS Content Renderer — Renders content blocks and page sections
 * Used by public pages to pull content from database
 */
class ContentRenderer {

    /**
     * Render a full page from CMS data
     */
    public static function renderPage(string $slug): string {
        $cms = CMS::getInstance();
        $page = $cms->getPage($slug);
        if (!$page) return '';

        // Track page view
        $cms->trackPageView($slug);

        $html = '';

        // Render hero section
        $html .= self::renderHero($page);

        // Render content blocks
        $blocks = $cms->getBlocks($page['id']);
        foreach ($blocks as $block) {
            $html .= self::renderBlock($block);
        }

        // If page has raw content, render it
        if (!empty($page['content']) && empty($blocks)) {
            $html .= '<section class="cms-content-section"><div class="container">' . $page['content'] . '</div></section>';
        }

        return $html;
    }

    /**
     * Render a hero section from page data or banner data
     */
    public static function renderHero(array $page, ?array $banner = null): string {
        $title = $banner['title'] ?? $page['hero_title'] ?? $page['title'] ?? '';
        $subtitle = $banner['subtitle'] ?? $page['hero_subtitle'] ?? $page['subtitle'] ?? '';
        $image = $banner['image_url'] ?? $page['hero_image'] ?? '/images/hero1.jpg';
        $overlay = $banner['overlay_color'] ?? $page['hero_overlay_color'] ?? 'rgba(26,35,126,0.75)';
        $textColor = $banner['text_color'] ?? '#ffffff';
        $position = $banner['text_position'] ?? 'center';
        $linkUrl = $banner['link_url'] ?? null;
        $linkText = $banner['link_text'] ?? null;

        $posClass = match($position) {
            'left' => 'text-start',
            'right' => 'text-end',
            'bottom-left' => 'text-start align-self-end',
            default => 'text-center',
        };

        $html = '<section class="cms-hero" style="background-image: url(' . htmlspecialchars($image) . ');">';
        $html .= '<div class="cms-hero-overlay" style="background: ' . htmlspecialchars($overlay) . ';"></div>';
        $html .= '<div class="cms-hero-content ' . $posClass . '" style="color: ' . htmlspecialchars($textColor) . ';">';
        $html .= '<div class="container">';
        if ($title) $html .= '<h1 class="cms-hero-title animate-on-scroll" data-animation="fade-up">' . htmlspecialchars($title) . '</h1>';
        if ($subtitle) $html .= '<p class="cms-hero-subtitle animate-on-scroll" data-animation="fade-up" data-delay="100">' . htmlspecialchars($subtitle) . '</p>';
        if ($linkUrl && $linkText) {
            $html .= '<a href="' . htmlspecialchars($linkUrl) . '" class="cms-btn cms-btn-primary animate-on-scroll" data-animation="fade-up" data-delay="200">' . htmlspecialchars($linkText) . '</a>';
        }
        $html .= '</div></div>';
        $html .= '<div class="cms-scroll-indicator"><div class="cms-scroll-arrow"></div></div>';
        $html .= '</section>';
        return $html;
    }

    /**
     * Render a single content block
     */
    public static function renderBlock(array $block): string {
        $animation = $block['animation'] ?? 'fade-up';
        $bgStyle = $block['background_style'] ?? '';
        $textColor = $block['text_color'] ?? '';

        $sectionStyle = '';
        if ($bgStyle) $sectionStyle .= "background: $bgStyle;";
        if ($textColor) $sectionStyle .= "color: $textColor;";

        $html = '<section class="cms-block cms-block-' . htmlspecialchars($block['block_type']) . '" ' . ($sectionStyle ? 'style="' . htmlspecialchars($sectionStyle) . '"' : '') . '>';
        $html .= '<div class="container">';

        // Block header
        if (!empty($block['title'])) {
            $html .= '<div class="cms-block-header animate-on-scroll" data-animation="' . htmlspecialchars($animation) . '">';
            $html .= '<h2 class="cms-block-title">' . htmlspecialchars($block['title']) . '</h2>';
            if (!empty($block['subtitle'])) {
                $html .= '<p class="cms-block-subtitle">' . htmlspecialchars($block['subtitle']) . '</p>';
            }
            $html .= '</div>';
        }

        // Block content based on type
        $html .= '<div class="cms-block-content animate-on-scroll" data-animation="' . htmlspecialchars($animation) . '" data-delay="100">';
        $html .= match($block['block_type']) {
            'text', 'html' => self::renderTextBlock($block),
            'stats' => self::renderStatsBlock($block),
            'cards' => self::renderCardsBlock($block),
            'timeline' => self::renderTimelineBlock($block),
            'testimonials' => self::renderTestimonialsBlock($block),
            'cta' => self::renderCTABlock($block),
            'faq', 'accordion' => self::renderFAQBlock($block),
            'gallery' => self::renderGalleryBlock($block),
            'image' => self::renderImageBlock($block),
            'video' => self::renderVideoBlock($block),
            'map' => self::renderMapBlock($block),
            default => '<div class="cms-raw-content">' . ($block['content'] ?? '') . '</div>',
        };
        $html .= '</div></div></section>';
        return $html;
    }

    private static function renderTextBlock(array $block): string {
        return '<div class="cms-text-content">' . ($block['content'] ?? '') . '</div>';
    }

    private static function renderStatsBlock(array $block): string {
        $stats = [
            ['value' => '2000', 'label' => 'Students Trained', 'icon' => 'fas fa-user-graduate', 'suffix' => '+'],
            ['value' => '25', 'label' => 'Years of Excellence', 'icon' => 'fas fa-award', 'suffix' => '+'],
            ['value' => '95', 'label' => 'Employment Rate', 'icon' => 'fas fa-briefcase-medical', 'suffix' => '%'],
            ['value' => '50', 'label' => 'Clinical Partners', 'icon' => 'fas fa-hospital', 'suffix' => '+'],
        ];

        $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : null;
        if ($settings && is_array($settings)) $stats = $settings;

        $html = '<div class="cms-stats-grid">';
        foreach ($stats as $stat) {
            $html .= '<div class="cms-stat-item">';
            $html .= '<div class="cms-stat-icon"><i class="' . htmlspecialchars($stat['icon'] ?? 'fas fa-chart-line') . '"></i></div>';
            $html .= '<div class="cms-stat-number" data-count="' . htmlspecialchars($stat['value'] ?? '0') . '">0</div>';
            $html .= '<div class="cms-stat-suffix">' . htmlspecialchars($stat['suffix'] ?? '') . '</div>';
            $html .= '<div class="cms-stat-label">' . htmlspecialchars($stat['label'] ?? '') . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function renderCardsBlock(array $block): string {
        $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : null;
        $items = $settings['items'] ?? [
            ['icon' => 'fas fa-medal', 'title' => 'Excellence', 'text' => 'Award-winning healthcare education with nationally recognized programs.'],
            ['icon' => 'fas fa-hand-holding-heart', 'title' => 'Compassion', 'text' => 'Training caring professionals dedicated to serving communities.'],
            ['icon' => 'fas fa-lightbulb', 'title' => 'Innovation', 'text' => 'Modern curriculum integrating latest healthcare practices and technology.'],
            ['icon' => 'fas fa-globe-africa', 'title' => 'Impact', 'text' => 'Graduates serving in hospitals and health centers across Uganda and beyond.'],
        ];

        $html = '<div class="cms-cards-grid">';
        foreach ($items as $item) {
            $html .= '<div class="cms-card">';
            $html .= '<div class="cms-card-icon"><i class="' . htmlspecialchars($item['icon'] ?? 'fas fa-star') . '"></i></div>';
            $html .= '<h3 class="cms-card-title">' . htmlspecialchars($item['title'] ?? '') . '</h3>';
            $html .= '<p class="cms-card-text">' . htmlspecialchars($item['text'] ?? '') . '</p>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function renderTimelineBlock(array $block): string {
        $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : null;
        $items = $settings['items'] ?? [
            ['year' => '1997', 'title' => 'School Founded', 'description' => 'Iganga School of Nursing and Midwifery established.'],
            ['year' => '2005', 'title' => 'Diploma Programs', 'description' => 'Introduction of Diploma programs.'],
            ['year' => '2015', 'title' => 'New Campus', 'description' => 'Expansion to new campus facilities.'],
        ];

        $html = '<div class="cms-timeline">';
        foreach ($items as $i => $item) {
            $side = ($i % 2 === 0) ? 'left' : 'right';
            $html .= '<div class="cms-timeline-item cms-timeline-' . $side . '">';
            $html .= '<div class="cms-timeline-dot"></div>';
            $html .= '<div class="cms-timeline-content">';
            $html .= '<span class="cms-timeline-year">' . htmlspecialchars($item['year'] ?? '') . '</span>';
            $html .= '<h3>' . htmlspecialchars($item['title'] ?? '') . '</h3>';
            $html .= '<p>' . htmlspecialchars($item['description'] ?? '') . '</p>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function renderTestimonialsBlock(array $block): string {
        $cms = CMS::getInstance();
        $testimonials = $cms->getTestimonials(true);

        if (empty($testimonials)) {
            $html = '<div class="cms-testimonials-slider">';
            $html .= '<div class="cms-testimonial-card">';
            $html .= '<p class="cms-testimonial-text">"ISNM gave me the foundation I needed to become a competent nurse."</p>';
            $html .= '<div class="cms-testimonial-author"><strong>Sarah Nambogo</strong><span>Registered Nurse, Mulago Hospital</span></div>';
            $html .= '</div></div>';
            return $html;
        }

        $html = '<div class="cms-testimonials-slider">';
        foreach ($testimonials as $t) {
            $html .= '<div class="cms-testimonial-card">';
            $html .= '<div class="cms-testimonial-stars">';
            for ($i = 0; $i < (int)($t['rating'] ?? 5); $i++) {
                $html .= '<i class="fas fa-star"></i>';
            }
            $html .= '</div>';
            $html .= '<p class="cms-testimonial-text">"' . htmlspecialchars($t['content'] ?? '') . '"</p>';
            $html .= '<div class="cms-testimonial-author">';
            if (!empty($t['author_image'])) {
                $html .= '<img src="' . htmlspecialchars($t['author_image']) . '" alt="' . htmlspecialchars($t['author_name'] ?? '') . '" class="cms-testimonial-avatar">';
            }
            $html .= '<div><strong>' . htmlspecialchars($t['author_name'] ?? '') . '</strong>';
            $html .= '<span>' . htmlspecialchars($t['author_title'] ?? '') . '</span></div>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function renderCTABlock(array $block): string {
        $html = '<div class="cms-cta-section">';
        $html .= '<div class="cms-cta-content">';
        if (!empty($block['title'])) $html .= '<h2>' . htmlspecialchars($block['title']) . '</h2>';
        if (!empty($block['subtitle'])) $html .= '<p>' . htmlspecialchars($block['subtitle']) . '</p>';
        $html .= '<div class="cms-cta-buttons">';
        $html .= '<a href="/application.php" class="cms-btn cms-btn-primary cms-btn-lg">Apply Now</a>';
        $html .= '<a href="/contact.php" class="cms-btn cms-btn-outline cms-btn-lg">Contact Us</a>';
        $html .= '</div></div></div>';
        return $html;
    }

    private static function renderFAQBlock(array $block): string {
        $cms = CMS::getInstance();
        $pageSlug = 'general';
        $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : null;
        if ($settings && !empty($settings['page_slug'])) $pageSlug = $settings['page_slug'];

        $faqs = $cms->getFAQs($pageSlug);
        if (empty($faqs)) return '<p class="text-muted">No FAQs available.</p>';

        $html = '<div class="cms-faq-accordion" id="faqAccordion">';
        foreach ($faqs as $i => $faq) {
            $html .= '<div class="cms-faq-item">';
            $html .= '<button class="cms-faq-question" onclick="toggleFaq(' . $i . ')" aria-expanded="false">';
            $html .= '<span>' . htmlspecialchars($faq['question']) . '</span>';
            $html .= '<i class="fas fa-chevron-down cms-faq-icon"></i>';
            $html .= '</button>';
            $html .= '<div class="cms-faq-answer" id="faq-answer-' . $i . '">';
            $html .= '<p>' . htmlspecialchars($faq['answer']) . '</p>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        $html .= '<script>function toggleFaq(i){const a=document.getElementById("faq-answer-"+i);const b=a.previousElementSibling;const c=b.classList.contains("active");document.querySelectorAll(".cms-faq-question").forEach(q=>{q.classList.remove("active");q.setAttribute("aria-expanded","false")});document.querySelectorAll(".cms-faq-answer").forEach(ans=>ans.classList.remove("active"));if(!c){b.classList.add("active");b.setAttribute("aria-expanded","true");a.classList.add("active")}}</script>';
        return $html;
    }

    private static function renderGalleryBlock(array $block): string {
        $cms = CMS::getInstance();
        $images = $cms->getGalleryImages(null, 12);
        if (empty($images)) return '<p class="text-muted">No gallery images available.</p>';

        $html = '<div class="cms-gallery-grid">';
        foreach ($images as $img) {
            $html .= '<div class="cms-gallery-item">';
            $html .= '<a href="' . htmlspecialchars($img['image_url']) . '" data-lightbox="gallery">';
            $html .= '<img src="' . htmlspecialchars($img['thumbnail_url'] ?? $img['image_url']) . '" alt="' . htmlspecialchars($img['alt_text'] ?? $img['title'] ?? '') . '" loading="lazy">';
            if (!empty($img['title'])) {
                $html .= '<div class="cms-gallery-overlay"><span>' . htmlspecialchars($img['title']) . '</span></div>';
            }
            $html .= '</a></div>';
        }
        $html .= '</div>';
        return $html;
    }

    private static function renderImageBlock(array $block): string {
        $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : null;
        $src = $settings['src'] ?? ($block['content'] ?? '');
        $alt = $settings['alt'] ?? $block['title'] ?? '';
        $caption = $settings['caption'] ?? '';
        $html = '<div class="cms-image-block">';
        $html .= '<img src="' . htmlspecialchars($src) . '" alt="' . htmlspecialchars($alt) . '" loading="lazy" class="cms-img-fluid">';
        if ($caption) $html .= '<p class="cms-image-caption">' . htmlspecialchars($caption) . '</p>';
        $html .= '</div>';
        return $html;
    }

    private static function renderVideoBlock(array $block): string {
        $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : null;
        $url = $settings['url'] ?? $block['content'] ?? '';
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            $videoId = '';
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $m)) {
                $videoId = $m[1];
            }
            return '<div class="cms-video-embed"><iframe src="https://www.youtube.com/embed/' . htmlspecialchars($videoId) . '" frameborder="0" allowfullscreen loading="lazy"></iframe></div>';
        }
        return '<div class="cms-video-embed"><video controls><source src="' . htmlspecialchars($url) . '">Your browser does not support video.</video></div>';
    }

    private static function renderMapBlock(array $block): string {
        $settings = !empty($block['settings']) ? json_decode($block['settings'], true) : null;
        $embedUrl = $settings['embed_url'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d63371.71536035884!2d33.6!3d0.7!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177db5e7b7f5e5e5%3A0x1234567890abcdef!2sIganga%2C%20Uganda!5e0!3m2!1sen!2sug';
        if ($settings && !empty($settings['embed_url'])) $embedUrl = $settings['embed_url'];
        return '<div class="cms-map-embed"><iframe src="' . htmlspecialchars($embedUrl) . '" width="100%" height="450" style="border:0;" allowfullscreen loading="lazy"></iframe></div>';
    }

    /**
     * Render SEO meta tags for a page
     */
    public static function renderSEO(array $page): string {
        $cms = CMS::getInstance();
        $html = '';
        $title = $page['meta_title'] ?? ($page['title'] . ' | ' . $cms->getSetting('school_name'));
        $desc = $page['meta_description'] ?? $cms->getSetting('default_meta_description');
        $ogTitle = $page['og_title'] ?? $title;
        $ogDesc = $page['og_description'] ?? $desc;
        $ogImage = $page['og_image'] ?? '/images/hero1.jpg';
        $canonical = $page['canonical_url'] ?? null;

        $html .= '<title>' . htmlspecialchars($title) . '</title>' . "\n";
        $html .= '<meta name="description" content="' . htmlspecialchars($desc) . '">' . "\n";
        if ($canonical) $html .= '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">' . "\n";
        $html .= '<meta property="og:title" content="' . htmlspecialchars($ogTitle) . '">' . "\n";
        $html .= '<meta property="og:description" content="' . htmlspecialchars($ogDesc) . '">' . "\n";
        $html .= '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";
        $html .= '<meta property="og:type" content="' . htmlspecialchars($page['og_type'] ?? 'website') . '">' . "\n";
        $html .= '<meta property="og:locale" content="' . htmlspecialchars($page['og_locale'] ?? 'en_US') . '">' . "\n";
        $html .= '<meta name="twitter:card" content="' . htmlspecialchars($page['twitter_card'] ?? 'summary_large_image') . '">' . "\n";
        $html .= '<meta name="twitter:title" content="' . htmlspecialchars($ogTitle) . '">' . "\n";
        $html .= '<meta name="twitter:description" content="' . htmlspecialchars($ogDesc) . '">' . "\n";
        $html .= '<meta name="twitter:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";

        // Schema.org structured data
        if (!empty($page['schema_type'])) {
            $schema = ['@context' => 'https://schema.org', '@type' => $page['schema_type']];
            if (!empty($page['schema_data'])) {
                $schema = array_merge($schema, json_decode($page['schema_data'], true) ?: []);
            } else {
                $schema['name'] = $page['title'] ?? '';
                $schema['description'] = $desc;
                $schema['url'] = $page['canonical_url'] ?? '';
            }
            $html .= '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
        }

        return $html;
    }
}
