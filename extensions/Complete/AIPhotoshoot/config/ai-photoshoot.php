<?php

declare(strict_types=1);

return [
    'version' => 1.0,

    /*
    |--------------------------------------------------------------------------
    | Max Images
    |--------------------------------------------------------------------------
    |
    | Maximum number of images the user can request in a single generation.
    |
    */
    'max_images' => 4,

    /*
    |--------------------------------------------------------------------------
    | Resolutions / Ratios
    |--------------------------------------------------------------------------
    */
    'resolutions' => ['1K', '2K', '4K'],

    'ratios' => [
        'auto' => 'Auto',
        '21:9' => '21:9 (Cinematic)',
        '16:9' => '16:9 (Widescreen)',
        '3:2'  => '3:2 (Landscape)',
        '4:3'  => '4:3 (Standard Landscape)',
        '1:1'  => '1:1 (Square)',
        '4:5'  => '4:5 (Portrait)',
        '3:4'  => '3:4 (Standard Portrait)',
        '9:16' => '9:16 (Vertical)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Templates (static)
    |--------------------------------------------------------------------------
    |
    | Preset photoshoot templates. Each entry drives a card on the Template
    | flow page and supplies a predefined prompt body.
    |
    */
    'templates' => [
        [
            'slug'     => 'studio',
            'label'    => 'Studio',
            'category' => 'Professional Photography',
            'image'    => 'vendor/ai-photoshoot/images/templates/studio.jpg',
            'prompt'   => 'A polished commercial product photograph of the attached product. Preserve the product\'s exact shape, color, label, branding, and proportions exactly as shown in the reference, but re-pose it upright and perfectly intact (closed, sealed, assembled — no floating caps, no tilted angles, no exploded views). Place the product as the hero subject, centered and isolated on a seamless neutral backdrop in soft white, light grey, or warm beige. Soft diffused key light from upper-left with gentle fill, a clean subtle contact shadow directly beneath the product anchoring it to the surface, sharp focus across the product, rich material and texture detail. Eye-level camera, no props, no environment, pure studio aesthetic, photorealistic, advertising-grade quality.',
        ],
        [
            'slug'     => 'in-use',
            'label'    => 'In Use',
            'category' => 'Interacting with the product',
            'image'    => 'vendor/ai-photoshoot/images/templates/in-use.jpg',
            'prompt'   => 'A candid lifestyle photograph of a real person actively using the attached product in the way it is naturally used — applying it, wearing it, holding it, pouring it, operating it, or interacting with it however fits the product category. Preserve the product\'s exact shape, color, label, and branding. Show genuine human interaction: a hand, face, or body engaging with the product mid-action, with an authentic expression. Natural soft daylight or warm indoor lighting, a real-world setting that matches the product\'s use case (bathroom for skincare, kitchen for food, desk for tech, etc.). Shallow depth of field, the product remains the clear focal point and is fully readable. Photorealistic, warm, human, believable moment.',
        ],
        [
            'slug'     => 'lifestyle',
            'label'    => 'Lifestyle',
            'category' => 'Places in a real-world scenario',
            'image'    => 'vendor/ai-photoshoot/images/templates/lifestyle.jpg',
            'prompt'   => 'A lifestyle photograph of the attached product placed in a real-world environment where it would naturally live — a kitchen counter, bathroom shelf, desk, dining table, nightstand, café table, or outdoor setting appropriate to the product category. Preserve the product\'s exact shape, color, label, and branding, and show it upright and intact. Soft natural window light or warm ambient light, a few tasteful supporting details (a plant, a book, a cup, a folded towel) that establish context without crowding the frame. Believable depth of field with gentle background blur, eye-level or slight three-quarter angle. The product is the unmistakable focal point and feels at home in the space. Photorealistic, warm, inviting.',
        ],
        [
            'slug'     => 'ugc',
            'label'    => 'UGC',
            'category' => 'Relatable social media vibe',
            'image'    => 'vendor/ai-photoshoot/images/templates/ugc.jpg',
            'prompt'   => 'An authentic amateur smartphone photo of the attached product, as if shot by a real customer on an iPhone to post on Instagram or TikTok. Preserve the product\'s exact shape, color, label, and branding. Casual hand-held framing (slightly off-center or tilted), mixed everyday indoor lighting (overhead lamp + window light, not studio lighting), mild grain, soft imperfect focus, slight motion blur acceptable. A real human hand holding the product, or the product casually placed on a normal everyday surface (kitchen counter with crumbs, nightstand with clutter, bathroom sink, car dashboard). Visible everyday context in the background — not styled, not curated. Direct flash optional. Explicitly NOT a studio shot, NOT a flatlay, NOT a polished commercial photograph, NOT a clean white background. Looks unedited, unstyled, real — the kind of photo a friend would text you.',
        ],
        [
            'slug'     => 'billboard',
            'label'    => 'Billboard',
            'category' => 'On a real outdoor billboard',
            'image'    => 'vendor/ai-photoshoot/images/templates/billboard.jpg',
            'prompt'   => 'A large outdoor advertising billboard featuring the attached product as the central hero of the campaign artwork printed on the billboard face. Preserve the product\'s exact shape, color, label, and branding on the billboard print. The billboard is a real physical structure with a rectangular frame, visible steel support posts or truss, mounted high above the ground. Shot from a low upward angle that emphasizes scale. The setting can be either a clean bright blue sky with soft clouds, or a real urban environment (city street, highway overpass, commercial district) — choose whichever feels more striking for the product. Natural daylight or golden-hour sunlight, photorealistic perspective, the billboard dominates the composition. Advertising campaign aesthetic, hyper-realistic.',
        ],
        [
            'slug'     => 'front-view',
            'label'    => 'Front View',
            'category' => 'eCommerce Thumbnail',
            'image'    => 'vendor/ai-photoshoot/images/templates/front-view.jpg',
            'prompt'   => 'A clean straight-on eCommerce catalog thumbnail of the attached product. Preserve the product\'s exact shape, color, label, and branding. The product stands upright, perfectly vertical, perpendicular to the camera, fully closed and intact (no floating caps, no tilt, no angled perspective, no exploded view). Pure pristine white background (#FFFFFF), product perfectly centered with generous balanced negative space on all sides, eye-level camera, even soft diffused lighting from both sides eliminating harsh shadows. A subtle realistic contact shadow directly beneath the product grounds it to the surface. Sharp edges, accurate true-to-life color, crisp focus across the entire product, no props, no environment, no reflections. Marketplace catalog ready (Amazon/Shopify style).',
        ],
        [
            'slug'     => 'flatlay',
            'label'    => 'Flatlay',
            'category' => 'A 90-degree overhead',
            'image'    => 'vendor/ai-photoshoot/images/templates/flatlay.jpg',
            'prompt'   => 'A strict 90-degree top-down overhead flatlay featuring the attached product as the centerpiece. Preserve the product\'s exact shape, color, label, and branding, and lay it flat on its side or stand it (whichever reads best from above). The surface should be a tasteful texture that complements the product — light wood, white marble, natural linen, kraft paper, or pastel paper. Arrange a small curated set of coherent supporting props around the product (a sprig of greenery, a folded cloth, a pen, a small bowl, beans, petals — choose items that thematically relate to the product). Even soft overhead lighting, soft natural shadows directly beneath each item, balanced negative space, intentional grid-like composition. Camera perfectly perpendicular to the surface — no perspective distortion. Photorealistic, magazine-quality.',
        ],
        [
            'slug'     => 'editorial',
            'label'    => 'Editorial',
            'category' => 'High-end, artistic composition',
            'image'    => 'vendor/ai-photoshoot/images/templates/editorial.jpg',
            'prompt'   => 'A high-end editorial image of the attached product in the style of a luxury magazine spread (Vogue, Kinfolk, Cereal). Preserve the product\'s exact shape, color, label, and branding, and re-pose it upright and intact. Cinematic directional lighting with strong shadow play (a beam of light, a window slat, a dramatic side-light), refined restrained color palette (deep tones, muted neutrals, or rich monochrome), deliberate generous negative space, artistic asymmetric framing. Optional moody backdrop (concrete pedestal, draped silk, textured plaster wall, polished stone). Sophisticated, considered, premium fine-art commercial photography. Photorealistic, atmospheric, unmistakably high-end.',
        ],
        [
            'slug'     => 'composition',
            'label'    => 'Composition',
            'category' => 'Artistic arrangement of relevant items',
            'image'    => 'vendor/ai-photoshoot/images/templates/composition.jpg',
            'prompt'   => 'A styled product composition with the attached product as the unmistakable hero, surrounded by a curated set of supporting items that genuinely relate to it — accessories, complementary objects, or thematic props that reinforce the product\'s story. Preserve the product\'s exact shape, color, label, and branding, and show it upright and intact. Use stepped geometric pedestals, blocks, or platforms at varying heights to create visual rhythm, with a cohesive monochromatic or harmonious color palette across the background and props. Even soft studio lighting with crisp clean shadows, balanced composition, the product elevated as the focal point. Modern, playful, magazine-quality still life, photorealistic.',
        ],
        [
            'slug'     => 'ingredient-based',
            'label'    => 'Ingredient-based',
            'category' => 'Surrounds with raw materials',
            'image'    => 'vendor/ai-photoshoot/images/templates/ingredient-based.jpg',
            'prompt'   => 'The attached product surrounded by the raw materials or ingredients it is plausibly made from — botanicals, leaves, and oil droplets for skincare; coffee beans and pods for coffee; fresh fruits and splashes for beverages; grains, herbs, and spices for food; fabric swatches for apparel; circuit boards or metal for tech — choose ingredients that authentically match the product category. Preserve the product\'s exact shape, color, label, and branding, and show it upright and intact as the centerpiece. Bright clean studio lighting, vibrant saturated natural colors, rich macro textures, ingredients arranged playfully and tastefully around (and lightly overlapping) the product to communicate authenticity, freshness, and provenance. Soft pastel or natural surface background. Photorealistic, fresh, appetizing.',
        ],
        [
            'slug'     => 'floating',
            'label'    => 'Floating',
            'category' => 'Suspended in mid-air at a Dynamic Angle',
            'image'    => 'vendor/ai-photoshoot/images/templates/floating.jpg',
            'prompt'   => 'The attached product suspended in mid-air at a dynamic three-quarter angle with no visible support, no strings, no hands. Preserve the product\'s exact shape, color, label, and branding, and show it intact (closed, sealed, assembled). Clean minimal background — either a smooth gradient (light grey to white, or soft pastel) or a solid neutral color. A soft realistic drop shadow on the surface below directly beneath the product, indicating its floating height and convincingly anchoring it to the scene. Razor-sharp focus on the product, soft even diffused lighting, modern weightless advertising aesthetic. Photorealistic, clean, premium tech/beauty campaign feel.',
        ],
        [
            'slug'     => 'contextual',
            'label'    => 'Contextual',
            'category' => 'Minimalist',
            'image'    => 'vendor/ai-photoshoot/images/templates/contextual.jpg',
            'prompt'   => 'The attached product in a minimalist restrained scene with only one or two essential contextual elements that subtly hint at where the product lives or how it is used (a single folded towel, one leaf, a small dish, a corner of a book — never more). Preserve the product\'s exact shape, color, label, and branding, and show it upright and intact. Soft directional natural lighting from one side, generous negative space dominating the frame, a calm uncluttered quiet mood. Smooth muted background (soft grey, beige, or pastel) with a subtle reflection or shadow on a glossy surface beneath. The product breathes — nothing competes for attention. Refined, elegant, photorealistic, premium minimalism.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backgrounds (static)
    |--------------------------------------------------------------------------
    |
    | Preset background images for the Replace Background flow. User-uploaded
    | backgrounds live in the `ai_photo_studio_backgrounds` table instead.
    |
    */
    'backgrounds' => [
        ['slug' => 'brown-curtain',       'label' => 'Brown',        'category' => 'Curtain', 'image' => 'vendor/ai-photoshoot/images/bgs/brown-curtain.jpg'],
        ['slug' => 'brown-studio',        'label' => 'Brown',        'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/brown-studio.jpg'],
        ['slug' => 'brown2-curtain',      'label' => 'Brown 2',      'category' => 'Curtain', 'image' => 'vendor/ai-photoshoot/images/bgs/brown2-curtain.jpg'],
        ['slug' => 'creamy-silk-studio',  'label' => 'Creamy Silk',  'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/creamy-silk-studio.jpg'],
        ['slug' => 'dark-studio',         'label' => 'Dark',         'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/dark-studio.jpg'],
        ['slug' => 'dark-noir-curtain',   'label' => 'Dark Noir',    'category' => 'Curtain', 'image' => 'vendor/ai-photoshoot/images/bgs/dark-noir-curtain.jpg'],
        ['slug' => 'florist-studio',      'label' => 'Florist',      'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/florist-studio.jpg'],
        ['slug' => 'green-curtain',       'label' => 'Green',        'category' => 'Curtain', 'image' => 'vendor/ai-photoshoot/images/bgs/green-curtain.jpg'],
        ['slug' => 'green-studio',        'label' => 'Green',        'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/green-studio.jpg'],
        ['slug' => 'house-minimal',       'label' => 'House',        'category' => 'Minimal', 'image' => 'vendor/ai-photoshoot/images/bgs/house-minimal.jpg'],
        ['slug' => 'kitchen-house',       'label' => 'Kitchen',      'category' => 'House',   'image' => 'vendor/ai-photoshoot/images/bgs/kitchen-house.jpg'],
        ['slug' => 'light-brown-curtain', 'label' => 'Light Brown',  'category' => 'Curtain', 'image' => 'vendor/ai-photoshoot/images/bgs/light-brown-curtain.jpg'],
        ['slug' => 'marble-studio',       'label' => 'Marble',       'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/marble-studio.jpg'],
        ['slug' => 'minimal-curtain',     'label' => 'Minimal',      'category' => 'Curtain', 'image' => 'vendor/ai-photoshoot/images/bgs/minimal-curtain.jpg'],
        ['slug' => 'minimal-studio',      'label' => 'Minimal',      'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/minimal-studio.jpg'],
        ['slug' => 'minimal-dark-studio', 'label' => 'Minimal Dark', 'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/minimal-dark-studio.jpg'],
        ['slug' => 'nature-studio',       'label' => 'Nature',       'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/nature-studio.jpg'],
        ['slug' => 'navy-curtain',        'label' => 'Navy',         'category' => 'Curtain', 'image' => 'vendor/ai-photoshoot/images/bgs/navy-curtain.jpg'],
        ['slug' => 'pink-curtain',        'label' => 'Pink',         'category' => 'Curtain', 'image' => 'vendor/ai-photoshoot/images/bgs/pink-curtain.jpg'],
        ['slug' => 'pinky-studio',        'label' => 'Pinky',        'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/pinky-studio.jpg'],
        ['slug' => 'shadow-studio',       'label' => 'Shadow',       'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/shadow-studio.jpg'],
        ['slug' => 'stand-minimal',       'label' => 'Stand',        'category' => 'Minimal', 'image' => 'vendor/ai-photoshoot/images/bgs/stand-minimal.jpg'],
        ['slug' => 'surface-studio',      'label' => 'Surface',      'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/surface-studio.jpg'],
        ['slug' => 'velvet-studio',       'label' => 'Velvet',       'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/velvet-studio.jpg'],
        ['slug' => 'white-silk-studio',   'label' => 'White Silk',   'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/white-silk-studio.jpg'],
        ['slug' => 'wood-studio',         'label' => 'Wood',         'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/wood-studio.jpg'],
        ['slug' => 'wooden-studio',       'label' => 'Wooden',       'category' => 'Studio',  'image' => 'vendor/ai-photoshoot/images/bgs/wooden-studio.jpg'],
    ],
];
