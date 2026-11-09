<?php
declare(strict_types=1);
/**
 * Flora Guard brand mark: the tree-in-viewfinder illustration with the
 * gold "lens" accent (the real app-icon source), rendered as an <img>.
 *
 * Callers may set $brandLogoSize (px, logical display size) before
 * requiring this file to pick an appropriately sized source asset from
 * assets/img/logo-mark-{32,48,64,96,180,256,512}.png. Defaults to 96.
 */
$brandLogoSize = isset($brandLogoSize) ? (int)$brandLogoSize : 96;
$brandLogoAvailableSizes = [32, 48, 64, 96, 180, 256, 512];
$brandLogoSrcSize = end($brandLogoAvailableSizes);
foreach ($brandLogoAvailableSizes as $brandLogoOption) {
    if ($brandLogoOption >= $brandLogoSize) {
        $brandLogoSrcSize = $brandLogoOption;
        break;
    }
}
?>
<img
    class="tg-logo-img"
    src="assets/img/logo-mark-<?= (int)$brandLogoSrcSize ?>.png"
    alt="Flora Guard logo"
    width="<?= (int)$brandLogoSize ?>"
    height="<?= (int)$brandLogoSize ?>"
    loading="eager"
>
