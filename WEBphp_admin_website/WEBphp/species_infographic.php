<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/flora_species.php';

// Accept ?id=anahaw or ?slug=anahaw
$id = (string)($_GET['id'] ?? $_GET['slug'] ?? '');
if ($id === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Missing id parameter';
    exit;
}

$profile = tg_flora_species_by_id($id);
if (!$profile) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Species not found';
    exit;
}

// load template
$tplPath = __DIR__ . '/assets/img/templates/species-infographic.svg.tpl';
if (!is_file($tplPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Template not found';
    exit;
}
$tpl = file_get_contents($tplPath);

// helper to build content blocks using safe escaping
function esc(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// build content HTML (we'll inject SVG <foreignObject> with HTML blocks)
$inner = '';

// Description block
$inner .= '<g transform="translate(500,140)">';
$inner .= '<text x="0" y="0" class="block-title">Description</text>';
$descHtml = '<div style="font-family:Arial, sans-serif;color:#333;font-size:14px;">'
    . nl2br(esc($profile['description'] ?? '')) . '</div>';
$inner .= '<foreignObject x="0" y="20" width="380" height="120"><div xmlns="http://www.w3.org/1999/xhtml">' . $descHtml . '</div></foreignObject>';

// description details grid
$inner .= '<foreignObject x="0" y="150" width="380" height="300"><div xmlns="http://www.w3.org/1999/xhtml" style="display:flex;flex-wrap:wrap;gap:8px;">';
foreach ($profile['description_details'] ?? [] as $k => $v) {
    $label = esc(ucfirst(str_replace('_', ' ', $k)));
    $val = esc($v);
    $inner .= '<div style="flex:1 1 45%;background:#fff;border-radius:8px;padding:8px;box-shadow:0 2px 6px rgba(0,0,0,0.06)"><div style="font-weight:700;color:#2d5016;font-size:13px;">' . $label . '</div><div style="font-size:13px;color:#4b4b4b;">' . $val . '</div></div>';
}
$inner .= '</div></foreignObject>';
$inner .= '</g>';

// Taxonomy block
$inner .= '<g transform="translate(900,140)">';
$inner .= '<text x="0" y="0" class="block-title">Taxonomy</text>';
$y = 28;
$ranks = [
    'Kingdom' => $profile['kingdom'] ?? '',
    'Division' => $profile['division'] ?? '',
    'Class' => $profile['class'] ?? '',
    'Order' => $profile['order'] ?? '',
    'Family' => $profile['family'] ?? '',
    'Genus' => $profile['genus'] ?? '',
    'Species' => $profile['species'] ?? '',
];
foreach ($ranks as $rank => $val) {
    $inner .= '<text x="0" y="' . $y . '" class="text" style="font-weight:700">' . esc($rank) . ': <tspan style="font-weight:400">' . esc($val) . '</tspan></text>';
    $y += 22;
}
$inner .= '</g>';

// Practical uses block (beneath description)
$inner .= '<g transform="translate(500,420)">';
$inner .= '<text x="0" y="0" class="block-title">Practical Uses</text>';
$inner .= '<foreignObject x="0" y="18" width="780" height="200"><div xmlns="http://www.w3.org/1999/xhtml" style="display:flex;flex-wrap:wrap;gap:8px;">';
foreach ($profile['practical_uses'] ?? [] as $use => $text) {
    $label = esc(ucfirst(str_replace('_', ' ', $use)));
    $val = esc($text);
    $inner .= '<div style="flex:1 1 30%;background:#fff;border-radius:8px;padding:8px;"><div style="font-weight:700;color:#8b6914;font-size:13px;">' . $label . '</div><div style="font-size:13px;color:#4b4b4b;">' . $val . '</div></div>';
}
$inner .= '</div></foreignObject>';
$inner .= '</g>';

// Replace placeholders
// Prepare image data URI to avoid external references in the SVG
$imageHref = 'assets/img/placeholder.png';
if (!empty($profile['image'])) {
    $imgPath = $profile['image'];
    // Resolve local paths
    $imageData = false;
    if (preg_match('#^https?://#i', $imgPath)) {
        // remote URL - try to fetch briefly
        $ctx = stream_context_create(['http' => ['timeout' => 3]]);
        $data = @file_get_contents($imgPath, false, $ctx);
        if ($data !== false) {
            $imageData = $data;
        }
    } else {
        // local path: try absolute and relative
        $try = $imgPath;
        if (strpos($try, '/') === 0) {
            $file = __DIR__ . $try;
            if (is_file($file)) {
                $imageData = @file_get_contents($file);
            }
        } else {
            $file = __DIR__ . '/' . ltrim($try, '/');
            if (is_file($file)) {
                $imageData = @file_get_contents($file);
            }
        }
    }

    if ($imageData !== false && $imageData !== null) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);
        if ($mime === false) {
            $mime = 'image/jpeg';
        }
        $dataUri = 'data:' . $mime . ';base64,' . base64_encode($imageData);
        $imageHref = $dataUri;
    } else {
        // keep a web-path placeholder if available
        $imageHref = 'assets/img/placeholder.png';
    }
}

$replacements = [
    '{{COMMON}}' => esc($profile['common'] ?? ''),
    '{{SCIENTIFIC}}' => esc($profile['scientific'] ?? ''),
    '{{IMAGE}}' => $imageHref,
    '{{CONTENT}}' => $inner,
];

$svg = str_replace(array_keys($replacements), array_values($replacements), $tpl);

// output
header('Content-Type: image/svg+xml; charset=utf-8');
echo $svg;
exit;
