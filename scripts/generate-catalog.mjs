import fs from 'node:fs';
import { csvEscape } from './lib/catalog.mjs';

const concepts = [
  ['Celestial Thuluth Crescent', 'Islamic Wall Art', 'islamic-calligraphy', 'A crescent-led Thuluth composition with open negative space for a calm focal wall.', ['MDF', 'Acrylic']],
  ['Mehrab Noor Calligraphy Panel', 'Arabic Calligraphy', 'islamic-calligraphy', 'An architectural arch silhouette framing an original flowing calligraphy layout.', ['MDF', 'Mirror acrylic']],
  ['Sabr & Shukr Balance Pair', 'Arabic Calligraphy', 'islamic-calligraphy', 'A balanced two-piece concept expressing patience and gratitude in a restrained modern form.', ['Acrylic', 'MDF']],
  ['Rahma Garden Calligraphy', 'Arabic Calligraphy', 'islamic-calligraphy', 'A botanical linework concept built around an Arabic expression of mercy, pending script approval.', ['Plywood', 'Acrylic']],
  ['Tawakkul Horizon Triptych', 'Arabic Calligraphy', 'islamic-calligraphy', 'A wide three-panel composition with a rising horizon rhythm for lounges and offices.', ['MDF']],
  ['Noor Arch Layered Artwork', 'Islamic Wall Art', 'islamic-calligraphy', 'Layered arches and controlled shadows create depth around a central verified calligraphy insert.', ['MDF', 'Acrylic']],
  ['Barakah Entryway Medallion', 'Arabic Calligraphy', 'islamic-calligraphy', 'A compact medallion concept intended as a welcoming entryway accent.', ['Acrylic']],
  ['Dua Serenity Vertical Panel', 'Islamic Wall Art', 'islamic-calligraphy', 'A tall, quiet composition designed for narrow walls and reflective corners.', ['Plywood', 'MDF']],
  ['Geometric Kufic Light Panel', 'Arabic Calligraphy', 'islamic-calligraphy', 'A square geometric Kufic-inspired study with production-safe bridges to be validated.', ['MDF', 'Mirror acrylic']],
  ['Tasbeeh Rhythm Wall Set', 'Islamic Wall Art', 'islamic-calligraphy', 'A modular three-piece wall set using repetition and spacing to suggest a contemplative rhythm.', ['Acrylic', 'MDF']],
  ['Ayatul Kursi Halo Round', 'Ayatul Kursi Wall Art', 'quranic-art', 'A circular halo composition reserved for fully verified Ayatul Kursi artwork.', ['MDF', 'Mirror acrylic']],
  ['Ayatul Kursi Grand Arch', 'Ayatul Kursi Wall Art', 'quranic-art', 'A statement arch concept that separates script, border and mounting layers for clarity.', ['MDF', 'Acrylic']],
  ['Ayatul Kursi Linear Panorama', 'Ayatul Kursi Wall Art', 'quranic-art', 'A panoramic proposal for wide feature walls, with readable spacing prioritized over ornament.', ['Acrylic']],
  ['Surah Al-Ikhlas Quiet Square', 'Quranic Surah Wall Art', 'quranic-art', 'A minimal square composition for verified Surah Al-Ikhlas text and restrained geometry.', ['MDF']],
  ['Four Qul Protective Quartet', 'Quranic Surah Wall Art', 'quranic-art', 'A coordinated four-panel concept; every panel remains blocked from publication until text review.', ['MDF', 'Acrylic']],
  ['Surah Ar-Rahman Refrain Panel', 'Quranic Surah Wall Art', 'quranic-art', 'A vertical feature panel inspired by the surah’s recurring visual cadence, pending verification.', ['Plywood', 'Acrylic']],
  ['Bismillah Welcome Sweep', 'Bismillah & Alhamdulillah Art', 'bismillah-kalma', 'A graceful horizontal Bismillah concept for entryways and dining spaces.', ['Acrylic']],
  ['Alhamdulillah Sunburst', 'Bismillah & Alhamdulillah Art', 'bismillah-kalma', 'A radial design with an uncluttered centre and fine sunburst perimeter.', ['MDF', 'Mirror acrylic']],
  ['Bismillah & Alhamdulillah Duo', 'Bismillah & Alhamdulillah Art', 'bismillah-kalma', 'A coordinated pair with mirrored proportions for balanced room placement.', ['MDF', 'Acrylic']],
  ['Shahada Horizon Statement', 'Kalma & Shahada Calligraphy', 'bismillah-kalma', 'A wide Shahada composition planned for high legibility and respectful visual hierarchy.', ['MDF']],
  ['Kalma Tayyaba Arch Set', 'Kalma & Shahada Calligraphy', 'bismillah-kalma', 'A three-arch layout with connected visual rhythm and manually verified script.', ['Acrylic', 'MDF']],
  ['Ayatul Kursi Orbit Clock', 'Islamic Wall Clocks', 'islamic-clock', 'A clock concept with time markers kept visually separate from verified sacred text.', ['MDF', 'Acrylic']],
  ['Bismillah Dawn Wall Clock', 'Islamic Wall Clocks', 'islamic-clock', 'A refined clock face with a sunrise-inspired upper arc and reserved calligraphy zone.', ['Plywood', 'Acrylic']],
  ['Allah & Muhammad ﷺ Balance Clock', 'Islamic Wall Clocks', 'islamic-clock', 'A symmetrical clock proposal with paired calligraphic forms and a quiet central dial.', ['MDF', 'Mirror acrylic']],
  ['Mehrab Silhouette Prayer Clock', 'Islamic Wall Clocks', 'islamic-clock', 'An arch-shaped clock concept suited to a prayer area, pending movement and artwork tests.', ['MDF']],
  ['Ramadan Moon Phase Clock', 'Islamic Wall Clocks', 'islamic-clock', 'A seasonal crescent-inspired clock with non-literal moon-phase geometry.', ['Acrylic', 'MDF']],
  ['Walnut Meridian Wall Clock', 'Modern Wall Clocks', 'modern-decor', 'A warm minimalist clock concept with offset markers and a slim visual profile.', ['Plywood']],
  ['Champagne Arc Wall Clock', 'Modern Wall Clocks', 'modern-decor', 'Overlapping arcs form a sculptural clock face designed for contemporary living rooms.', ['Acrylic', 'MDF']],
  ['Desert Dune Layered Wall Sculpture', 'Acrylic Wall Décor', 'modern-decor', 'Layered flowing contours create a serene abstract landscape without figurative imagery.', ['MDF', 'Acrylic']],
  ['Mashrabiya Shadow Grid', 'CNC Geometric Wall Panels', 'modern-decor', 'A modular geometric panel designed to cast changing shadows throughout the day.', ['MDF', 'Plywood']],
  ['Golden Ratio Botanical Panels', 'Acrylic Wall Décor', 'modern-decor', 'A two-panel botanical abstraction organized with measured arcs and negative space.', ['Acrylic', 'MDF']],
  ['Walnut Eight-Point Star Panel', 'Wooden & MDF Wall Art', 'material-led', 'A warm star-grid panel that adapts Islamic geometric logic for a modern interior.', ['Plywood', 'MDF']],
  ['Mirror Acrylic Infinity Rosette', 'Acrylic Wall Décor', 'material-led', 'A reflective rosette concept with nested rings and carefully spaced cut-outs.', ['Mirror acrylic', 'MDF']],
  ['CNC Interlace Triptych', 'CNC Geometric Wall Panels', 'material-led', 'Three aligned panels form one continuous interlace across a feature wall.', ['MDF']],
  ['Natural Wood Arch Landscape', 'Wooden & MDF Wall Art', 'material-led', 'A layered non-figurative landscape composed from arches, horizon lines and warm grain.', ['Plywood']],
  ['Black & Gold Faceted Wall Set', 'Acrylic Wall Décor', 'material-led', 'A faceted three-piece décor set using matte and reflective surfaces for contrast.', ['Acrylic', 'MDF']],
  ['Family Name Heritage Monogram', 'Personalized Name Wall Art', 'personalized', 'A custom Latin-letter family monogram with an optional establishment year.', ['MDF', 'Acrylic']],
  ['Newlywed Names & Date Arch', 'Wedding & Housewarming Gifts', 'personalized', 'A wedding keepsake concept for approved names and date in an architectural frame.', ['Mirror acrylic', 'MDF']],
  ['Nursery Moon Name Sign', 'Personalized Name Wall Art', 'personalized', 'A gentle moon-and-stars name sign for non-religious custom text.', ['Plywood', 'Acrylic']],
  ['Bespoke Calligraphy Commission', 'Custom Islamic Calligraphy', 'personalized', 'A request-led design service with mandatory source, script and production approval.', ['MDF', 'Acrylic', 'Plywood']]
];

const slugify = value => value.toLowerCase().normalize('NFKD').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
const categorySlugs = {
  'Islamic Wall Art': 'islamic-wall-art',
  'Ayatul Kursi Wall Art': 'ayatul-kursi-wall-art',
  'Bismillah & Alhamdulillah Art': 'bismillah-alhamdulillah-art',
  'Kalma & Shahada Calligraphy': 'kalma-shahada-calligraphy',
  'Quranic Surah Wall Art': 'quranic-surah-wall-art',
  'Arabic Calligraphy': 'arabic-calligraphy',
  'Islamic Wall Clocks': 'islamic-wall-clocks',
  'Modern Wall Clocks': 'modern-wall-clocks',
  'Acrylic Wall Décor': 'acrylic-wall-decor',
  'Wooden & MDF Wall Art': 'wooden-mdf-wall-art',
  'CNC Geometric Wall Panels': 'cnc-geometric-wall-panels',
  'Personalized Name Wall Art': 'personalized-name-wall-art',
  'Custom Islamic Calligraphy': 'custom-islamic-calligraphy',
  'Wedding & Housewarming Gifts': 'wedding-housewarming-gifts'
};

const products = concepts.map(([name, category, group, short, materials], index) => {
  const sku = `NS-${String(index + 1).padStart(3, '0')}`;
  const religious = ['islamic-calligraphy', 'quranic-art', 'bismillah-kalma', 'islamic-clock'].includes(group) || name.includes('Calligraphy');
  const clock = group === 'islamic-clock' || category === 'Modern Wall Clocks';
  const personalized = group === 'personalized';
  const finishes = materials.includes('Plywood')
    ? ['Natural Wood', 'Walnut', 'Matte Black', 'White']
    : ['Matte Black', 'Gloss Black', 'Gold', 'Silver', 'White'];
  return {
    external_id: `noorshine-catalog-v1-${String(index + 1).padStart(3, '0')}`,
    sku,
    name,
    slug: slugify(name.replace('ﷺ', '')),
    type: 'variable',
    status: 'draft',
    catalog_visibility: 'hidden',
    group,
    categories: [categorySlugs[category]],
    tags: [...new Set(['NoorShine original concept', category, ...materials, personalized ? 'Personalized' : 'Made to order concept'])],
    short_description: short,
    description: `${short} This original NoorShine concept is intended for CNC-cut or layered fabrication after material, thickness, mounting and structural constraints are confirmed. ${religious ? 'The Arabic or religious artwork must be checked against an approved source and manually signed off before production or publication.' : 'Final artwork must pass a production review before the product can be published.'} Size, finish and pricing selections shown in the planning catalog are proposals, not confirmed manufacturing claims.`,
    materials,
    finishes,
    sizes: ['12 inches', '18 inches', '24 inches', '30 inches', '36 inches'],
    dimensions: null,
    dimensions_note: 'Exact width, height, depth and piece spacing are unverified; confirm from production artwork for each selected size.',
    weight: null,
    care_instructions: 'Dust gently with a clean, soft, dry microfibre cloth. Avoid water, sprays, abrasive cleaners, high humidity and prolonged direct sunlight until the selected finish has been tested.',
    installation_instructions: 'Mounting hardware and wall compatibility are not yet confirmed. Supply an installation guide matched to the final weight, wall type and hanging system before fulfilment.',
    customization: {
      eligible: personalized,
      allowed: personalized ? 'Approved non-religious names, dates or short text; religious requests require manual review.' : 'Finish and size proposals only until production rules are approved.',
      manual_approval_required: religious || personalized
    },
    clock_movement: clock ? 'Unverified; owner must approve movement, hand clearance and battery specification.' : null,
    manufacturing_cost: null,
    selling_price: null,
    profit_margin: null,
    competitor_reference_price: null,
    competitor_source_url: null,
    price_status: 'awaiting-owner-costing-and-approval',
    production_status: 'concept-only',
    seo: {
      title: `${name} | NoorShine Pakistan`,
      meta_description: `${short} View the original NoorShine concept, proposed finishes and customization status for customers in Pakistan.`.slice(0, 155),
      focus_keyphrase: `${name} Pakistan`
    },
    images: {
      status: 'required',
      requirements: [`Front-facing product cutout on warm ivory`, `Styled room mockup with truthful scale reference`, `Close detail of proposed ${materials[0]} finish`, 'Installation and packaging reference after verification'],
      alt_text: `${name} original NoorShine wall décor concept in a proposed premium finish`,
      reuse_competitor_images: false
    },
    verification: {
      production: 'pending',
      dimensions: 'pending',
      weight: 'pending',
      material_thickness: 'pending',
      religious_text: religious ? 'manual-approval-required' : 'not-applicable',
      pricing: 'pending',
      images: 'pending'
    }
  };
});

fs.mkdirSync('data', { recursive: true });
fs.writeFileSync('data/products.json', `${JSON.stringify(products, null, 2)}\n`);

const columns = ['external_id', 'sku', 'name', 'slug', 'status', 'group', 'categories', 'tags', 'short_description', 'description', 'materials', 'finishes', 'sizes', 'dimensions', 'dimensions_note', 'weight', 'care_instructions', 'installation_instructions', 'customization', 'manufacturing_cost', 'selling_price', 'profit_margin', 'competitor_reference_price', 'competitor_source_url', 'price_status', 'production_status', 'seo', 'images', 'verification'];
const lines = [columns.map(csvEscape).join(','), ...products.map(product => columns.map(column => csvEscape(product[column])).join(','))];
fs.writeFileSync('data/products.csv', `${lines.join('\n')}\n`);
console.log(`Generated ${products.length} draft products in data/products.json and data/products.csv`);

