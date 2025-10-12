
<?php
// search.php — MolecularMap Search with Home link and working API lookups

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

function fetch_pubchem($query) {
    $url = "https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/name/" . urlencode($query) . "/property/MolecularFormula,MolecularWeight,InChIKey/JSON";
    $resp = @file_get_contents($url);
    return $resp ? json_decode($resp, true) : null;
}

function fetch_chembl($query) {
    $url = "https://www.ebi.ac.uk/chembl/api/data/molecule/" . urlencode($query) . ".json";
    $resp = @file_get_contents($url);
    return $resp ? json_decode($resp, true) : null;
}

function fetch_uniprot($query) {
    $url = "https://rest.uniprot.org/uniprotkb/search?query=" . urlencode($query) . "&format=json&size=3";
    $resp = @file_get_contents($url);
    return $resp ? json_decode($resp, true) : null;
}

function fetch_pubmed($query) {
    $url = "https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pubmed&retmode=json&retmax=3&term=" . urlencode($query);
    $resp = @file_get_contents($url);
    return $resp ? json_decode($resp, true) : null;
}

function is_disease_query($query) {
    $keywords = ["cancer","diabetes","alzheimer","parkinson","disease","syndrome","tumor","infection","virus","bacteria"];
    $q = strtolower($query);
    foreach ($keywords as $kw) {
        if (strpos($q, $kw) !== false) return true;
    }
    return false;
}

$graph_nodes = [];
$graph_edges = [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>MolecularMap Search</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; margin: 2rem; }
    h1 { color: #0f766e; }
    nav { margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; }
    nav a { text-decoration: none; color: #0f766e; font-weight: bold; margin-right: 1rem; }
    form { margin-bottom: 2rem; }
    input[type=text] { padding: 0.6rem; width: 300px; border: 1px solid #ccc; border-radius: 4px; }
    button { padding: 0.6rem 1rem; background: #0f766e; border: none; border-radius: 4px; color: white; font-weight: bold; cursor: pointer; }
    .results { margin-top: 2rem; }
    .box { background: #f0fdfa; padding: 1rem; margin: 1rem 0; border-radius: 6px; border: 1px solid #e2e8f0; }
    #cy { width: 100%; height: 400px; border: 1px solid #ccc; margin-top: 1.5rem; border-radius: 6px; }
  </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.24.0/cytoscape.min.js"></script>
</head>
<body>
  <nav>
    <a href="index.html">🏠 Home</a>
    <a href="search.php">🔍 Search</a>
  </nav>

  <h1>MolecularMap Search</h1>
  <form method="GET" action="search.php">
    <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Enter molecule, SMILES, or disease..." required>
    <button type="submit">Search</button>
  </form>

  <?php if ($query): ?>
  <div class="results">
    <h2>Results for: "<?= htmlspecialchars($query) ?>"</h2>

    <?php if (is_disease_query($query)): ?>
      <?php $pubmed = fetch_pubmed($query); ?>
      <div class="box">
        <h3>🧬 Disease-related PubMed Articles</h3>
        <?php if ($pubmed && isset($pubmed['esearchresult']['idlist'])): ?>
          <ul>
            <?php foreach ($pubmed['esearchresult']['idlist'] as $pmid): ?>
              <li><a href="https://pubmed.ncbi.nlm.nih.gov/<?= $pmid ?>" target="_blank">PubMed ID <?= $pmid ?></a></li>
              <?php $graph_nodes[] = ["data" => ["id" => "pmid$pmid", "label" => "PubMed $pmid"]]; ?>
              <?php $graph_edges[] = ["data" => ["source" => "query", "target" => "pmid$pmid", "type" => "dotted"]]; ?>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>No PubMed results found.</p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php
        $pubchem = fetch_pubchem($query);
        $chembl = fetch_chembl($query);
        $uniprot = fetch_uniprot($query);
      ?>
      <div class="box">
        <h3>🧪 PubChem</h3>
        <?php if ($pubchem && isset($pubchem['PropertyTable']['Properties'][0])): 
          $props = $pubchem['PropertyTable']['Properties'][0]; ?>
          <p><b>Formula:</b> <?= $props['MolecularFormula'] ?><br>
             <b>Weight:</b> <?= $props['MolecularWeight'] ?><br>
             <b>InChIKey:</b> <?= $props['InChIKey'] ?></p>
          <?php $graph_nodes[] = ["data" => ["id" => "pubchem", "label" => "PubChem"]]; ?>
          <?php $graph_edges[] = ["data" => ["source" => "query", "target" => "pubchem", "type" => "solid"]]; ?>
        <?php else: ?>
          <p>No PubChem data found.</p>
        <?php endif; ?>
      </div>

      <div class="box">
        <h3>🔗 ChEMBL</h3>
        <?php if ($chembl && isset($chembl['molecule_chembl_id'])): ?>
          <p><b>ChEMBL ID:</b> <?= $chembl['molecule_chembl_id'] ?></p>
          <?php $graph_nodes[] = ["data" => ["id" => "chembl", "label" => "ChEMBL"]]; ?>
          <?php $graph_edges[] = ["data" => ["source" => "query", "target" => "chembl", "type" => "solid"]]; ?>
        <?php else: ?>
          <p>No ChEMBL data found.</p>
        <?php endif; ?>
      </div>

      <div class="box">
        <h3>🧬 UniProt (Proteins)</h3>
        <?php if ($uniprot && isset($uniprot['results'])): ?>
          <ul>
            <?php foreach ($uniprot['results'] as $u): ?>
              <li><?= htmlspecialchars($u['primaryAccession']) ?> — <?= htmlspecialchars($u['uniProtkbId']) ?></li>
              <?php $graph_nodes[] = ["data" => ["id" => $u['primaryAccession'], "label" => $u['uniProtkbId']]]; ?>
              <?php $graph_edges[] = ["data" => ["source" => "query", "target" => $u['primaryAccession'], "type" => "dashed"]]; ?>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p>No UniProt results found.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Graph -->
    <div id="cy"></div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
      var cy = cytoscape({
        container: document.getElementById("cy"),
        elements: {
          nodes: [
            { data: { id: "query", label: "<?= htmlspecialchars($query) ?>" } },
            <?php foreach ($graph_nodes as $n) echo json_encode($n) . ","; ?>
          ],
          edges: [
            <?php foreach ($graph_edges as $e) echo json_encode($e) . ","; ?>
          ]
        },
        style: [
          { selector: "node", style: { "label": "data(label)", "background-color": "#0f766e", "color": "#fff", "text-valign": "center", "text-outline-width": 2, "text-outline-color": "#0f766e" }},
          { selector: "edge[type='solid']", style: { "line-color": "#2563eb", "width": 3 }},
          { selector: "edge[type='dashed']", style: { "line-color": "#16a34a", "line-style": "dashed", "width": 3 }},
          { selector: "edge[type='dotted']", style: { "line-color": "#dc2626", "line-style": "dotted", "width": 3 }}
        ],
        layout: { name: "cose" }
      });
    });
    </script>

    <p style="margin-top:2rem;">
      <a href="index.html" style="color:#0f766e; font-weight:bold;">⬅ Back to Home</a>
    </p>
  </div>
  <?php endif; ?>
</body>
</html>


