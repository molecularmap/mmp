<?php
// -------------------------------------------------------------------
// search_advanced.php  (conservative UI; complete file)
// - Keeps existing header/footer if header.php / footer.php are present
// - Removes AI-agent cards
// - Adds one dropdown (defaults to Auto) and shows results below the box
// -------------------------------------------------------------------

/** Basic escaping */
function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Small JSON fetcher with short timeouts */
function fetch_json($url, $timeout = 3.0) {
  $ch = curl_init();
  curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_USERAGENT => 'MolecularMap/advanced-search'
  ]);
  $raw  = curl_exec($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
  curl_close($ch);
  if ($err || $code >= 400 || !$raw) return null;
  $j = json_decode($raw, true);
  return is_array($j) ? $j : null;
}

/** Source handlers */
$SOURCES = [];

/* PubChem: compound name -> CIDs */
$SOURCES['pubchem'] = [
  'label' => 'PubChem (Compounds)',
  'handler' => function($q, $limit = 5) {
    $url = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/name/' . rawurlencode($q) . '/cids/JSON';
    $j = fetch_json($url);
    $out = [];
    if (!empty($j['IdentifierList']['CID'])) {
      foreach (array_slice($j['IdentifierList']['CID'], 0, $limit) as $cid) {
        $out[] = [
          'title' => "CID $cid",
          'url'   => "https://pubchem.ncbi.nlm.nih.gov/compound/$cid",
          'meta'  => 'Compound • PubChem'
        ];
      }
    }
    if (!$out) {
      $out[] = [
        'title' => 'Search on PubChem',
        'url'   => 'https://pubchem.ncbi.nlm.nih.gov/#query=' . rawurlencode($q),
        'meta'  => 'No exact API hit • Fallback link'
      ];
    }
    return $out;
  }
];

/* UniProt: proteins */
$SOURCES['uniprot'] = [
  'label' => 'UniProt (Proteins)',
  'handler' => function($q, $limit = 5) {
    $base = 'https://rest.uniprot.org/uniprotkb/search?format=json&size=' . (int)$limit . '&query=';
    $j = fetch_json($base . rawurlencode($q));
    $out = [];
    if (!empty($j['results'])) {
      foreach ($j['results'] as $r) {
        $acc  = $r['primaryAccession'] ?? '';
        $name = $r['proteinDescription']['recommendedName']['fullName']['value']
                ?? ($r['uniProtkbId'] ?? 'Protein');
        $out[] = [
          'title' => "$name ($acc)",
          'url'   => "https://www.uniprot.org/uniprotkb/" . rawurlencode($acc) . "/entry",
          'meta'  => 'Protein • UniProt'
        ];
      }
    }
    if (!$out) {
      $out[] = [
        'title' => 'Search on UniProt',
        'url'   => 'https://www.uniprot.org/uniprotkb?query=' . rawurlencode($q),
        'meta'  => 'No API hits • Fallback link'
      ];
    }
    return $out;
  }
];

/* PubMed: link out */
$SOURCES['pubmed'] = [
  'label' => 'PubMed (Literature)',
  'handler' => function($q, $limit = 5) {
    return [[
      'title' => 'Search on PubMed',
      'url'   => 'https://pubmed.ncbi.nlm.nih.gov/?term=' . rawurlencode($q),
      'meta'  => 'Literature • PubMed'
    ]];
  }
];

/* ChEMBL: link out */
$SOURCES['chembl'] = [
  'label' => 'ChEMBL (Molecules/Drugs)',
  'handler' => function($q, $limit = 5) {
    return [[
      'title' => 'Search on ChEMBL',
      'url'   => 'https://www.ebi.ac.uk/chembl/g/#search_results/all/' . rawurlencode($q),
      'meta'  => 'Molecules • ChEMBL'
    ]];
  }
];

/* KEGG: link out */
$SOURCES['kegg'] = [
  'label' => 'KEGG (Pathways)',
  'handler' => function($q, $limit = 5) {
    return [[
      'title' => 'Search on KEGG',
      'url'   => 'https://www.kegg.jp/kegg-bin/search?search_string=' . rawurlencode($q) . '&database=pathway',
      'meta'  => 'Pathways • KEGG'
    ]];
  }
];

/* Auto (default): combine a few top sources */
$AUTO_ENGINES = ['pubchem', 'uniprot', 'pubmed'];

/** Read inputs */
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$engine = isset($_GET['engine']) ? (string)$_GET['engine'] : '';
if (!$engine || !isset($SOURCES[$engine])) {
  $engine = 'auto';
}

/** Run search */
$results = [];
$error   = '';
if ($q !== '') {
  try {
    if ($engine === 'auto') {
      foreach ($AUTO_ENGINES as $e) {
        $res = $SOURCES[$e]['handler']($q, 5);
        if (is_array($res)) $results = array_merge($results, $res);
      }
    } else {
      $res = $SOURCES[$engine]['handler']($q, 10);
      if (is_array($res)) $results = $res;
    }
  } catch (Throwable $e) {
    $error = 'Search error: ' . $e->getMessage();
  }
}

/** Detect header/footer files to preserve your site chrome */
$HAS_HEADER = file_exists(__DIR__ . '/header.php');
$HAS_FOOTER = file_exists(__DIR__ . '/footer.php');

if ($HAS_HEADER) {
  include __DIR__ . '/header.php';
} else {
  // Minimal fallback header (only used if your header.php is missing)
  ?><!doctype html>
  <html lang="en"><head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>MolecularMap – Advanced Search</title>
    <style>
      /* Tiny neutral fallback; your real site styles come from header.php */
      body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial; margin:0; }
      .container { max-width: 980px; margin: 24px auto; padding: 0 16px; }
      .page-title { font-weight: 700; margin: 12px 0 16px; }
      .search-row { display: grid; grid-template-columns: 1fr 220px auto; gap: 8px; }
      .search-row input, .search-row select { padding: 10px; font-size: 15px; }
      .search-row button { padding: 10px 14px; font-weight: 600; }
      .results { margin-top: 14px; }
      .result { padding: 10px 0; border-top: 1px solid #eee; }
      .result:first-child { border-top: none; }
      .result-title a { color: #0b5cff; text-decoration: none; font-weight: 600; }
      .result-title a:hover { text-decoration: underline; }
      .result-meta { color: #666; font-size: 13px; margin-top: 2px; }
      .results-empty, .results-error { margin-top: 10px; color: #666; }
      @media (max-width: 720px) { .search-row { grid-template-columns: 1fr; } }
    </style>
  </head><body><div class="container"><h1 class="page-title">Advanced Search</h1>
  <?php
}
?>

<div class="container" id="advanced-search-content">
  <!-- Search row (keeps your styles if your CSS targets these classes/IDs) -->
  <form method="get" action="search_advanced.php" class="search-row" aria-label="Advanced search">
    <input
      type="text"
      name="q"
      value="<?=h($q)?>"
      placeholder="Search molecules, proteins, pathways, or literature…"
      required
      autocomplete="off"
      aria-label="Search"
      class="search-input"
    />
    <select name="engine" aria-label="Select source" class="search-select">
      <option value="auto" <?= $engine==='auto' ? 'selected' : '' ?>>Auto (recommended)</option>
      <?php foreach ($SOURCES as $key => $cfg): if ($key==='auto') continue; ?>
        <option value="<?=h($key)?>" <?= $engine === $key ? 'selected' : '' ?>>
          <?=h($cfg['label'])?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn search-btn">Search</button>
  </form>

  <!-- Results immediately below the search box -->
  <section id="results" class="results">
    <?php if ($q === ''): ?>
      <div class="results-empty">Enter a query to see results here.</div>
    <?php else: ?>
      <?php if ($error): ?>
        <div class="results-error"><?=h($error)?></div>
      <?php elseif (!$results): ?>
        <div class="results-empty">No results for <strong><?=h($q)?></strong> (source: <?=h($engine === 'auto' ? 'Auto' : $SOURCES[$engine]['label'])?>).</div>
      <?php else: ?>
        <?php foreach ($results as $r): ?>
          <div class="result">
            <div class="result-title">
              <a href="<?=h($r['url'] ?? '#')?>" target="_blank" rel="noopener noreferrer">
                <?=h($r['title'] ?? 'Result')?>
              </a>
            </div>
            <?php if (!empty($r['meta'])): ?>
              <div class="result-meta"><?=h($r['meta'])?></div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</div>

<?php
if ($HAS_FOOTER) {
  include __DIR__ . '/footer.php';
} else {
  // Minimal fallback footer (only used if footer.php is missing)
  ?></div></body></html><?php
}

