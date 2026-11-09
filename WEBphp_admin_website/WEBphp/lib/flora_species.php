<?php

declare(strict_types=1);

/**
 * Static species profiles for Flora Guard (Anahaw, Narra, Molave reference).
 *
 * @return list<array<string, mixed>>
 */
function tg_flora_species_profiles(): array
{
    return [
        [
            'id' => 'anahaw',
            'common' => 'Anahaw',
            'scientific' => 'Saribus rotundifolius',
            'image' => 'assets/img/anahaw.png',
            'kingdom' => 'Plantae',
            'phylum' => 'Tracheophyta',
            'class' => 'Liliopsida (Monocotyledonae)',
            'order' => 'Arecales',
            'family' => 'Arecaceae',
            'genus' => 'Saribus',
            'species' => 'rotundifolius',
            'origin' => 'Philippines',
            'description' =>
                'Saribus rotundifolius, commonly known as Anahaw or footstool palm, is a round-leaf fan palm '
                . 'native to the rainforests of Southeast Asia, particularly the Philippines. Characterized '
                . 'by its striking, glossy, fan-shaped leaves and slender, solitary trunk, it can grow up to '
                . '20 meters tall. It is highly regarded in Filipino culture, serving as the unofficial national '
                . 'leaf of the Philippines, and is widely harvested for its versatile leaves used in traditional '
                . 'thatched roofing (bahay kubo), food wrapping, fan making, and various handicrafts.',
            'conservation' => 'Culturally significant; widely cultivated',
            'description_details' => [
                'leaf_shape' => 'Fan-shaped, circular to broadly ovate',
                'petiole' => 'Spiny, long petiole',
                'trunk' => 'Unbranched, slender trunk',
                'flowers' => 'Small cream-colored flowers in clusters',
                'fruit' => 'Small, round drupes in clusters',
            ],
            'practical_uses' => [
                'roofing' => 'Traditional roofing and thatching for Bahay Kubo',
                'weaving' => 'Basketry and handicraft weaving',
                'fans' => 'Hand fans and decorative hats',
                'landscaping' => 'Ornamental plant in gardens and landscapes',
                'food' => 'Edible shoots and fruit; palm wine production (regional)',
            ],
            'cultural_significance' => [
                'national_symbol' => 'National leaf of the Philippines',
                'festivals' => 'Featured in cultural festivals and celebrations',
                'arts' => 'Used in traditional art and crafts',
            ],
            'distribution_habitat' => [
                'distribution' => 'Philippines, Indonesia, Malaysia, Thailand',
                'habitat' => 'Lowland rainforests, swamps, along streams',
                'climate' => 'Tropical and subtropical regions',
            ],
        ],
        [
            'id' => 'narra',
            'common' => 'Narra',
            'scientific' => 'Pterocarpus indicus',
            'image' => 'assets/img/narra.png',
            'kingdom' => 'Plantae',
            'phylum' => 'Tracheophyta',
            'class' => 'Magnoliopsida (Dicotyledonae)',
            'order' => 'Fabales',
            'family' => 'Fabaceae',
            'genus' => 'Pterocarpus',
            'species' => 'indicus',
            'origin' => 'Southeast Asia (including the Philippines)',
            'description' =>
                'Pterocarpus indicus, popularly known as Narra or the rosewood tree, is a majestic deciduous '
                . 'canopy tree native to Southeast Asia, northern Australasia, and the western Pacific islands. '
                . 'Declared the national tree of the Philippines, it is celebrated for its extreme durability, '
                . 'strength, and premium-quality reddish wood, which is highly sought after for fine furniture '
                . 'and woodcarving. The tree features a wide-spreading crown, fragrant yellow-orange flowers '
                . 'that bloom in spectacular short bursts, and flat, disc-shaped winged pods (samaras).',
            'conservation' => 'Protected in many areas; check local DENR listings',
            'description_details' => [
                'height' => 'Large tree, can reach 30-40 meters',
                'bark' => 'Rough, dark brown to black bark',
                'leaves' => 'Pinnate compound leaves with 7-17 leaflets',
                'flowers' => 'Showy yellow-orange flowers in panicles',
                'fruit' => 'Flat, winged pods containing seeds',
            ],
            'practical_uses' => [
                'furniture' => 'Premium hardwood for furniture and fine cabinetry',
                'construction' => 'Construction materials and structural timber',
                'carving' => 'Wood carving and handicrafts',
                'shade' => 'Excellent shade tree in parks and streets',
                'medicine' => 'Traditional medicinal uses for various ailments',
            ],
            'cultural_significance' => [
                'national_symbol' => 'National tree of the Philippines',
                'heritage' => 'Important in Filipino cultural heritage',
                'symbolism' => 'Represents strength and resilience',
            ],
            'distribution_habitat' => [
                'distribution' => 'Southeast Asia, Philippines, Indonesia, Malaysia',
                'habitat' => 'Lowland forests, coastal areas, disturbed sites',
                'climate' => 'Tropical climate, well-drained soils',
            ],
        ],
        [
            'id' => 'molave',
            'common' => 'Molave',
            'scientific' => 'Vitex parviflora',
            'image' => 'assets/img/molave.png',
            'kingdom' => 'Plantae',
            'phylum' => 'Tracheophyta',
            'class' => 'Magnoliopsida (Dicotyledonae)',
            'order' => 'Lamiales',
            'family' => 'Lamiaceae',
            'genus' => 'Vitex',
            'species' => 'parviflora',
            'origin' => 'Philippines and Southeast Asia',
            'description' =>
                'Vitex parviflora (Molave) is a hard, durable Philippine hardwood species valued for heavy '
                . 'construction and outdoor structures. Often used as a benchmark for strong native timber, '
                . 'it thrives in dry lowland forests and open areas.',
            'conservation' => 'Check local DENR listings for harvest restrictions',
            'description_details' => [
                'wood' => 'Very hard, dense heartwood',
                'height' => 'Medium to large tree in native forests',
                'leaves' => 'Opposite, compound leaves typical of Vitex',
                'flowers' => 'Small lavender to white flowers',
                'durability' => 'Highly resistant to decay and insects',
            ],
            'practical_uses' => [
                'construction' => 'Heavy construction and structural posts',
                'flooring' => 'Flooring and high-wear surfaces',
                'outdoor' => 'Outdoor structures and marine-related builds',
                'furniture' => 'Furniture requiring very hard wood',
            ],
            'cultural_significance' => [
                'timber' => 'Traditional benchmark for durable native hardwood',
                'heritage' => 'Widely referenced in Philippine forestry education',
            ],
            'distribution_habitat' => [
                'distribution' => 'Philippines and parts of Southeast Asia',
                'habitat' => 'Dry forests and open lowland areas',
                'climate' => 'Tropical; drought-tolerant on well-drained soils',
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>|null
 */
function tg_flora_species_by_id(string $id): ?array
{
    foreach (tg_flora_species_profiles() as $p) {
        if ($p['id'] === $id) {
            return $p;
        }
    }
    return null;
}
