<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/flora_species.php';

tg_require_login();

$pageTitle = 'Species guide';
$navActive = 'species';
$profiles = tg_flora_species_profiles();

require __DIR__ . '/includes/layout_start.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">

<div class="sg-wrap">

  <header class="sg-hero">
    <div class="sg-hero__leaf sg-hero__leaf--1">🌿</div>
    <div class="sg-hero__leaf sg-hero__leaf--2">🍃</div>
    <div class="sg-hero__leaf sg-hero__leaf--3">🌱</div>
    <div class="sg-hero__inner">
      <span class="sg-hero__badge">Flora Guard Dataset</span>
      <h1 class="sg-hero__title">Species Guide</h1>
      <p class="sg-hero__sub">Taxonomic reference for Philippine native flora — <em>Anahaw</em>, <em>Narra</em> &amp; <em>Molave</em></p>
    </div>
  </header>

  <div class="sg-grid">
    <?php foreach ($profiles as $p) : ?>

      <div class="sg-card">

        <!-- ═══ CARD BANNER ═══ -->
        <div class="sg-card__banner">
          <div class="sg-card__banner-bg"></div>
          <div class="sg-card__banner-content">
            <div class="sg-card__img-wrap">
              <?php if (!empty($p['image'])) : ?>
                <img src="<?= h($p['image']) ?>" alt="<?= h($p['common']) ?>"
                     class="sg-card__img"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="sg-card__img-fallback" style="display:none">
                  <img src="assets/img/placeholder.png" alt="<?= h($p['common']) ?>">
                </div>
              <?php else : ?>
                <div class="sg-card__img-fallback">
                  <img src="assets/img/placeholder.png" alt="<?= h($p['common']) ?>">
                </div>
              <?php endif; ?>
            </div>
            <div class="sg-card__identity">
              <h2 class="sg-card__name"><?= h($p['common']) ?></h2>
              <p class="sg-card__sci"><em><?= h($p['scientific']) ?></em></p>
              <div class="sg-card__badges">
                <span class="sg-badge sg-badge--origin"><?= h($p['origin']) ?></span>
                <span class="sg-badge sg-badge--conservation"><?= h($p['conservation']) ?></span>
              </div>
            </div>
          </div>
          <div class="sg-card__banner-strip"></div>
        </div>

        <!-- ═══ INFOGRAPHIC BODY ═══ -->
        <div class="sg-card__body">

          <!-- Row 1: Description + Taxonomy -->
          <div class="sg-row sg-row--top">

            <!-- DESCRIPTION -->
            <section class="sg-panel sg-panel--desc">
              <div class="sg-panel__header sg-panel__header--desc">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M2 20h20M6 20V10l6-8 6 8v10"/></svg>
                <span>Description</span>
              </div>
              <p class="sg-card__desc-text"><?= h($p['description'] ?? '') ?></p>
              <div class="sg-desc-grid">
                <?php foreach ($p['description_details'] as $feature => $desc) : ?>
                  <div class="sg-desc-item">
                    <div class="sg-desc-icon">
                      <?php echo sg_desc_icon($feature); ?>
                    </div>
                    <div class="sg-desc-text">
                      <span class="sg-desc-label"><?= h(ucfirst(str_replace('_', ' ', $feature))) ?></span>
                      <span class="sg-desc-val"><?= h($desc) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>

            <!-- TAXONOMY -->
            <section class="sg-panel sg-panel--tax">
              <div class="sg-panel__header sg-panel__header--tax">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                <span>Taxonomy</span>
              </div>
              <div class="sg-tax-tree">
                <?php
                $phylumVal = (string)($p['phylum'] ?? $p['division'] ?? '');
                $ranks = [
                  'Kingdom' => ['slug' => 'kingdom', 'icon' => '🌍', 'val' => $p['kingdom']],
                  'Phylum'  => ['slug' => 'phylum', 'icon' => '🌿', 'val' => $phylumVal],
                  'Class'   => ['slug' => 'class', 'icon' => '🍃', 'val' => $p['class']],
                  'Order'   => ['slug' => 'order', 'icon' => '🌺', 'val' => $p['order']],
                  'Family'  => ['slug' => 'family', 'icon' => '🌴', 'val' => $p['family']],
                  'Genus'   => ['slug' => 'genus', 'icon' => '🔬', 'val' => $p['genus']],
                  'Species' => ['slug' => 'species', 'icon' => '✳️', 'val' => $p['species']],
                ];
                foreach ($ranks as $rank => $info) :
                  $slug = (string)$info['slug'];
                ?>
                  <div class="sg-tax-row sg-tax-row--<?= h($slug) ?>">
                    <span class="sg-tax-icon"><?= $info['icon'] ?></span>
                    <span class="sg-tax-rank"><?= h($rank) ?></span>
                    <span class="sg-tax-val"><?= h((string)$info['val']) ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>

          </div><!-- /sg-row--top -->

          <!-- Row 2: Practical Uses -->
          <section class="sg-panel sg-panel--uses">
            <div class="sg-panel__header sg-panel__header--uses">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
              <span>Practical Uses</span>
            </div>
            <div class="sg-uses-grid">
              <?php foreach ($p['practical_uses'] as $use => $desc) : ?>
                <div class="sg-use-card">
                  <div class="sg-use-icon"><?php echo sg_use_icon($use); ?></div>
                  <div class="sg-use-label"><?= h(ucfirst(str_replace('_', ' ', $use))) ?></div>
                  <div class="sg-use-val"><?= h($desc) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>

          <!-- Row 3: Culture + Habitat -->
          <div class="sg-row sg-row--bottom">

            <!-- CULTURAL SIGNIFICANCE -->
            <section class="sg-panel sg-panel--culture">
              <div class="sg-panel__header sg-panel__header--culture">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <span>Cultural Significance</span>
              </div>
              <div class="sg-culture-list">
                <?php foreach ($p['cultural_significance'] as $aspect => $sig) : ?>
                  <div class="sg-culture-item">
                    <div class="sg-culture-dot"></div>
                    <div>
                      <span class="sg-culture-label"><?= h(ucfirst(str_replace('_', ' ', $aspect))) ?></span>
                      <span class="sg-culture-val"><?= h($sig) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </section>

            <!-- DISTRIBUTION & HABITAT -->
            <section class="sg-panel sg-panel--habitat">
              <div class="sg-panel__header sg-panel__header--habitat">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                <span>Distribution &amp; Habitat</span>
              </div>
              <div class="sg-habitat-list">
                <div class="sg-habitat-item">
                  <div class="sg-habitat-pill sg-habitat-pill--dist">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    Distribution
                  </div>
                  <p class="sg-habitat-val"><?= h($p['distribution_habitat']['distribution']) ?></p>
                </div>
                <div class="sg-habitat-item">
                  <div class="sg-habitat-pill sg-habitat-pill--hab">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0 1 10 10H2A10 10 0 0 1 12 2z"/><path d="M12 12v10"/><path d="M5 19l7-7 7 7"/></svg>
                    Habitat
                  </div>
                  <p class="sg-habitat-val"><?= h($p['distribution_habitat']['habitat']) ?></p>
                </div>
                <div class="sg-habitat-item">
                  <div class="sg-habitat-pill sg-habitat-pill--clim">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                    Climate
                  </div>
                  <p class="sg-habitat-val"><?= h($p['distribution_habitat']['climate']) ?></p>
                </div>
              </div>
            </section>

          </div><!-- /sg-row--bottom -->

        </div><!-- /sg-card__body -->
      </div><!-- /sg-card -->

    <?php endforeach; ?>
  </div><!-- /sg-grid -->
</div><!-- /sg-wrap -->

<?php
/* ── SVG icon helpers ── */
function sg_desc_icon(string $key): string {
  $icons = [
    'default' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>',
    'leaf'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 20A7 7 0 0 1 4 13c0-5 4-9 8-11 4 2 8 6 8 11a7 7 0 0 1-7 7z"/></svg>',
    'trunk'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="2" width="6" height="20" rx="3"/></svg>',
    'flower'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6M12 17v6M4.22 4.22l4.24 4.24M15.54 15.54l4.24 4.24M1 12h6M17 12h6M4.22 19.78l4.24-4.24M15.54 8.46l4.24-4.24"/></svg>',
    'fruit'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 5a5 5 0 0 1 10 0c0 5-5 13-5 13S7 10 7 5z"/></svg>',
    'height'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M5 12l7-10 7 10"/></svg>',
  ];
  foreach ($icons as $k => $svg) {
    if (str_contains(strtolower($key), $k)) return $svg;
  }
  return $icons['default'];
}

function sg_use_icon(string $key): string {
  $map = [
    'roof'    => '🏠',
    'thatch'  => '🏡',
    'weav'    => '🧺',
    'basket'  => '🧺',
    'fan'     => '🪭',
    'hat'     => '🎩',
    'food'    => '🍽️',
    'drink'   => '🍹',
    'wine'    => '🍾',
    'timber'  => '🪵',
    'wood'    => '🪵',
    'furni'   => '🪑',
    'medic'   => '💊',
    'orna'    => '🌺',
    'dye'     => '🎨',
    'shoot'   => '🌱',
    'fruit'   => '🍑',
  ];
  $k = strtolower($key);
  foreach ($map as $needle => $emoji) {
    if (str_contains($k, $needle)) return $emoji;
  }
  return '🔧';
}
?>

<style>
/* ══════════════════════════════════════════════
   DESIGN TOKENS
══════════════════════════════════════════════ */
:root {
  --sg-green-dark:   #1b4332;
  --sg-green-mid:    #2d6a4f;
  --sg-green-light:  #52b788;
  --sg-green-pale:   #d8f3dc;
  --sg-gold:         #d4a017;
  --sg-gold-light:   #fef3c7;
  --sg-amber:        #b45309;
  --sg-purple:       #6d28d9;
  --sg-purple-light: #ede9fe;
  --sg-blue:         #1d4ed8;
  --sg-blue-light:   #dbeafe;
  --sg-brown:        #92400e;
  --sg-tan:          #fef9c3;
  --sg-cream:        #fefce8;
  --sg-white:        #ffffff;
  --sg-gray-50:      #f8fafc;
  --sg-gray-100:     #f1f5f9;
  --sg-gray-200:     #e2e8f0;
  --sg-gray-600:     #475569;
  --sg-gray-800:     #1e293b;
  --sg-radius:       20px;
  --sg-radius-sm:    10px;
  --sg-shadow:       0 8px 40px rgba(27,67,50,.12), 0 2px 8px rgba(27,67,50,.08);
  --sg-shadow-panel: 0 2px 8px rgba(27,67,50,.07);
}

/* ══════════════════════════════════════════════
   WRAP + HERO
══════════════════════════════════════════════ */
.sg-wrap {
  font-family: 'DM Sans', sans-serif;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1.5rem 4rem;
}

.sg-hero {
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, var(--sg-green-dark) 0%, var(--sg-green-mid) 60%, #40916c 100%);
  border-radius: 0 0 var(--sg-radius) var(--sg-radius);
  padding: 3.5rem 2rem 3rem;
  text-align: center;
  margin: 0 -1.5rem 2.5rem;
}
.sg-hero__leaf {
  position: absolute;
  font-size: 6rem;
  opacity: .12;
  pointer-events: none;
  animation: sg-sway 6s ease-in-out infinite;
}
.sg-hero__leaf--1 { top: -1rem; left: -1rem; animation-delay: 0s; }
.sg-hero__leaf--2 { bottom: -1rem; right: -1rem; animation-delay: -2s; }
.sg-hero__leaf--3 { top: 50%; left: 50%; transform: translate(-50%,-50%); opacity: .06; font-size: 14rem; }
@keyframes sg-sway {
  0%,100% { transform: rotate(-5deg) scale(1); }
  50%      { transform: rotate(5deg) scale(1.05); }
}
.sg-hero__inner { position: relative; z-index: 1; }
.sg-hero__badge {
  display: inline-block;
  background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.3);
  color: #fff;
  font-size: .8rem;
  font-weight: 600;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  padding: .35rem .9rem;
  border-radius: 999px;
  margin-bottom: .75rem;
}
.sg-hero__title {
  font-family: 'Playfair Display', serif;
  font-size: clamp(2.2rem, 5vw, 3.8rem);
  font-weight: 900;
  color: #fff;
  margin: 0 0 .5rem;
  letter-spacing: -1px;
  line-height: 1.1;
}
.sg-hero__sub {
  color: rgba(255,255,255,.8);
  font-size: 1.05rem;
  margin: 0;
}

/* ══════════════════════════════════════════════
   SPECIES GRID
══════════════════════════════════════════════ */
.sg-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 640px), 1fr));
  gap: 2.5rem;
}

/* ══════════════════════════════════════════════
   CARD
══════════════════════════════════════════════ */
.sg-card {
  background: var(--sg-white);
  border-radius: var(--sg-radius);
  box-shadow: var(--sg-shadow);
  overflow: hidden;
  transition: transform .3s ease, box-shadow .3s ease;
}
.sg-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 60px rgba(27,67,50,.18);
}

/* BANNER */
.sg-card__banner {
  position: relative;
  background: linear-gradient(135deg, var(--sg-green-dark), #40916c);
  padding: 2rem 1.75rem 1.5rem;
  overflow: hidden;
}
.sg-card__banner-bg {
  position: absolute;
  inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.sg-card__banner-strip {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 6px;
  background: linear-gradient(90deg, var(--sg-gold), #f59e0b, var(--sg-gold));
}
.sg-card__banner-content {
  position: relative;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}
.sg-card__img-wrap {
  width: 100px;
  height: 100px;
  border-radius: 18px;
  overflow: hidden;
  border: 3px solid rgba(255,255,255,.35);
  box-shadow: 0 4px 20px rgba(0,0,0,.3);
  flex-shrink: 0;
  background: #2d6a4f;
}
.sg-card__img, .sg-card__img-fallback img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.sg-card__img-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.sg-card__name {
  font-family: 'Playfair Display', serif;
  font-size: 1.9rem;
  font-weight: 900;
  color: #fff;
  margin: 0 0 .25rem;
  line-height: 1.1;
}
.sg-card__sci {
  color: rgba(255,255,255,.75);
  font-size: 1rem;
  margin: 0 0 .75rem;
}
.sg-card__badges {
  display: flex;
  gap: .5rem;
  flex-wrap: wrap;
}
.sg-badge {
  font-size: .75rem;
  font-weight: 600;
  padding: .25rem .7rem;
  border-radius: 999px;
  letter-spacing: .5px;
}
.sg-badge--origin { background: rgba(255,255,255,.2); color: #fff; }
.sg-badge--conservation { background: var(--sg-gold); color: #1a1a1a; }

/* ══════════════════════════════════════════════
   CARD BODY
══════════════════════════════════════════════ */
.sg-card__body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* ROWS */
.sg-row {
  display: grid;
  gap: 1rem;
}
.sg-row--top    { grid-template-columns: 1fr 1fr; }
.sg-row--bottom { grid-template-columns: 1fr 1fr; }
@media (max-width: 700px) {
  .sg-row--top, .sg-row--bottom { grid-template-columns: 1fr; }
}

/* ══════════════════════════════════════════════
   PANELS
══════════════════════════════════════════════ */
.sg-panel {
  border-radius: var(--sg-radius-sm);
  padding: 1.1rem;
  box-shadow: var(--sg-shadow-panel);
}
.sg-panel--desc    { background: #f9f6ee; border: 1.5px solid #e6dfc4; }
.sg-panel--tax     { background: #f0f9f4; border: 1.5px solid #b7dfc8; }
.sg-panel--uses    { background: var(--sg-gold-light); border: 1.5px solid #fde68a; }
.sg-panel--culture { background: var(--sg-purple-light); border: 1.5px solid #c4b5fd; }
.sg-panel--habitat { background: var(--sg-blue-light); border: 1.5px solid #bfdbfe; }

.sg-panel__header {
  display: flex;
  align-items: center;
  gap: .5rem;
  font-family: 'Playfair Display', serif;
  font-size: .95rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: .9rem;
  padding-bottom: .6rem;
  border-bottom: 2px solid;
}
.sg-panel__header--desc    { color: var(--sg-brown); border-color: #e6dfc4; }
.sg-panel__header--tax     { color: var(--sg-green-dark); border-color: #b7dfc8; }
.sg-panel__header--uses    { color: var(--sg-amber); border-color: #fde68a; }
.sg-panel__header--culture { color: var(--sg-purple); border-color: #c4b5fd; }
.sg-panel__header--habitat { color: var(--sg-blue); border-color: #bfdbfe; }

.sg-card__desc-text {
  font-size: 0.9rem;
  color: #5c4a32;
  line-height: 1.5;
  margin: 0 0 1rem 0;
  padding: 0 0.25rem;
}

/* ── DESCRIPTION GRID ── */
.sg-desc-grid {
  display: flex;
  flex-direction: column;
  gap: .6rem;
}
.sg-desc-item {
  display: flex;
  align-items: flex-start;
  gap: .65rem;
  padding: .55rem .7rem;
  background: rgba(255,255,255,.7);
  border-radius: 8px;
  transition: background .2s;
}
.sg-desc-item:hover { background: rgba(255,255,255,.95); }
.sg-desc-icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  background: #d4a574;
  color: #fff;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.sg-desc-text { display: flex; flex-direction: column; }
.sg-desc-label {
  font-weight: 700;
  font-size: .8rem;
  color: var(--sg-brown);
  text-transform: uppercase;
  letter-spacing: .5px;
}
.sg-desc-val { font-size: .88rem; color: #5c4a32; line-height: 1.4; }

/* ── TAXONOMY TREE ── */
.sg-tax-tree {
  display: flex;
  flex-direction: column;
  gap: .35rem;
}
.sg-tax-row {
  display: flex;
  align-items: center;
  gap: .6rem;
  padding: .45rem .7rem;
  background: rgba(255,255,255,.65);
  border-radius: 8px;
  transition: background .2s;
}
.sg-tax-row:hover { background: rgba(255,255,255,.95); }
.sg-tax-icon { font-size: 1rem; flex-shrink: 0; }
.sg-tax-rank {
  flex: 0 0 5.5rem;
  font-weight: 700;
  font-size: .78rem;
  text-transform: uppercase;
  letter-spacing: .5px;
}
.sg-tax-val {
  font-size: .88rem;
  font-weight: 500;
}
.sg-tax-row--kingdom { border-left: 4px solid #5E35B1; background: rgba(94,53,177,.08); }
.sg-tax-row--kingdom .sg-tax-rank { color: #5E35B1; }
.sg-tax-row--kingdom .sg-tax-val { color: #4527A0; }
.sg-tax-row--phylum { border-left: 4px solid #00838F; background: rgba(0,131,143,.08); }
.sg-tax-row--phylum .sg-tax-rank { color: #00838F; }
.sg-tax-row--phylum .sg-tax-val { color: #006064; }
.sg-tax-row--class { border-left: 4px solid #2E7D32; background: rgba(46,125,50,.08); }
.sg-tax-row--class .sg-tax-rank { color: #2E7D32; }
.sg-tax-row--class .sg-tax-val { color: #1B5E20; }
.sg-tax-row--order { border-left: 4px solid #E65100; background: rgba(230,81,0,.08); }
.sg-tax-row--order .sg-tax-rank { color: #E65100; }
.sg-tax-row--order .sg-tax-val { color: #BF360C; }
.sg-tax-row--family { border-left: 4px solid #6D4C41; background: rgba(109,76,65,.08); }
.sg-tax-row--family .sg-tax-rank { color: #6D4C41; }
.sg-tax-row--family .sg-tax-val { color: #4E342E; }
.sg-tax-row--genus { border-left: 4px solid #3949AB; background: rgba(57,73,171,.08); }
.sg-tax-row--genus .sg-tax-rank { color: #3949AB; }
.sg-tax-row--genus .sg-tax-val { color: #283593; }
.sg-tax-row--species {
  border-left: 4px solid #1B5E20;
  background: rgba(27,94,32,.12);
}
.sg-tax-row--species .sg-tax-rank { color: #1B5E20; }
.sg-tax-row--species .sg-tax-val { color: #1B5E20; font-style: italic; font-weight: 700; }

/* ── PRACTICAL USES GRID ── */
.sg-uses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: .75rem;
}
.sg-use-card {
  background: rgba(255,255,255,.7);
  border-radius: var(--sg-radius-sm);
  padding: .85rem .7rem;
  text-align: center;
  transition: transform .2s, box-shadow .2s;
  cursor: default;
}
.sg-use-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 18px rgba(180,83,9,.15);
  background: rgba(255,255,255,.95);
}
.sg-use-icon { font-size: 1.8rem; margin-bottom: .4rem; }
.sg-use-label {
  display: block;
  font-weight: 700;
  font-size: .8rem;
  color: var(--sg-amber);
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: .25rem;
}
.sg-use-val { font-size: .82rem; color: #6b5a3a; line-height: 1.35; }

/* ── CULTURAL LIST ── */
.sg-culture-list { display: flex; flex-direction: column; gap: .6rem; }
.sg-culture-item {
  display: flex;
  align-items: flex-start;
  gap: .65rem;
  padding: .55rem .7rem;
  background: rgba(255,255,255,.65);
  border-radius: 8px;
  transition: background .2s;
}
.sg-culture-item:hover { background: rgba(255,255,255,.95); }
.sg-culture-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: var(--sg-purple);
  flex-shrink: 0;
  margin-top: .45rem;
  box-shadow: 0 0 0 3px rgba(109,40,217,.2);
}
.sg-culture-label {
  display: block;
  font-weight: 700;
  font-size: .8rem;
  color: var(--sg-purple);
  text-transform: uppercase;
  letter-spacing: .5px;
}
.sg-culture-val { font-size: .88rem; color: #5a4a6a; line-height: 1.4; }

/* ── HABITAT LIST ── */
.sg-habitat-list { display: flex; flex-direction: column; gap: .65rem; }
.sg-habitat-item { display: flex; flex-direction: column; gap: .35rem; }
.sg-habitat-pill {
  display: inline-flex;
  align-items: center;
  gap: .4rem;
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .7px;
  padding: .25rem .7rem;
  border-radius: 999px;
}
.sg-habitat-pill--dist { background: #dbeafe; color: var(--sg-blue); }
.sg-habitat-pill--hab  { background: var(--sg-green-pale); color: var(--sg-green-dark); }
.sg-habitat-pill--clim { background: #fef3c7; color: var(--sg-amber); }
.sg-habitat-val {
  margin: 0;
  font-size: .88rem;
  color: #3a5a7a;
  line-height: 1.45;
  padding-left: .25rem;
}

/* ══════════════════════════════════════════════
   ANIMATIONS (stagger on load)
══════════════════════════════════════════════ */
.sg-card {
  opacity: 0;
  transform: translateY(24px);
  animation: sg-fadein .6s ease forwards;
}
.sg-card:nth-child(1) { animation-delay: .1s; }
.sg-card:nth-child(2) { animation-delay: .25s; }
.sg-card:nth-child(3) { animation-delay: .4s; }
.sg-card:nth-child(4) { animation-delay: .55s; }
@keyframes sg-fadein {
  to { opacity: 1; transform: none; }
}

/* ══════════════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════════════ */
@media (max-width: 500px) {
  .sg-card__banner-content { flex-direction: column; align-items: flex-start; }
  .sg-uses-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<?php require __DIR__ . '/includes/layout_end.php'; ?>