
<?php
// search.php — MolecularMap Smart Search with Styled Graphs + Legend

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
    form { margin-bottom: 2rem; }
    input[type=text] {
      padding: 0.6rem; width: 300px;
      border: 1px solid #ccc; border-radius: 4px;
    }
    button {
      padding: 0.6rem 1rem; background: #0f766e; 
      border: none; border-radius: 4px;
      color: white; font-weight: bold; cursor: pointer;
    }
    .results { margin-top: 2rem; }
    .box {
      background: #f0fdfa; padding: 1rem; margin: 1rem 0;
      border-radius: 6px; border: 1px solid #e2e8f0;
    }
    #cy {
      width: 100%;
      height: 400px;
      border: 1px solid #ccc;
      margin-top: 1.5rem;
      border-radius: 6px;
    }
    #legend {
      margin-top: 1rem;
      padding: 0.8rem;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      background: #f9fafb;
      font-size: 0.9rem;
      width: fit-content;
    }
    .legend-item {
      display: flex;
      align-items: center;
      margin-bottom: 0.3rem;
    }
    .legend-line {
      width: 30px; height: 0; border-top-width: 3px;
      margin-right: 8px;
    }
    .solid-blue { border-top: 3px solid #2563eb; }
    .dashed-green { border-top: 3px dashed #16a34a; }
    .dotted-red { border-top: 3px dotted #dc2626; }
  </style>
  <!-- Cytoscape.js -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.24.0/cytoscape.min.js"></script>
</head>
<body>
  <h1>🔍 MolecularMap Search</h1>
  <form method="GET" action="search.php">
    <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="Enter molecule, SMILES, or disease..."/>
    <button type="submit">Search</button>
  </form>

  <?php if ($query): ?>
  <div class="results">
    <h2>Results for: "<?= htmlspecialchars($query) ?>"</h2>

    <?php if (is_disease_query($query)): ?>
      <!-- Disease-focused -->
      <div class="box">
        <h3>📚 PubMed (Disease-related Articles)</h3>
        <?php 
          $pm = fetch_pubmed($query);
          if ($pm && isset($pm['esearchresult']['idlist'])) {
              foreach ($pm['esearchresult']['idlist'] as $pmid) {
                  echo "<p><a href='https://pubmed.ncbi.nlm.nih.gov/$pmid/' target='_blank'>PubMed ID: $pmid</a></p>";
                  $graph_nodes[] = ["data" => ["id" => "pubmed_$pmid", "label" => "PubMed:$pmid"]];
                  $graph_edges[] = ["data" => ["source" => $query, "target" => "pubmed_$pmid", "label" => "article", "type" => "literature"]];
              }
          } else {
              echo "<p>No PubMed results found.</p>";
          }
        ?>
      </div>

      <div class="box">
        <h3>🧬 UniProt (Disease-related Proteins)</h3>
        <?php 
          $up = fetch_uniprot($query);
          if ($up && isset($up['results'])) {
              foreach ($up['results'] as $entry) {
                  echo "<p><b>" . htmlspecialchars($entry['primaryAccession']) . "</b>: " . htmlspecialchars($entry['uniProtkbId']) . "</p>";
                  $graph_nodes[] = ["data" => ["id" => $entry['primaryAccession'], "label" => $entry['uniProtkbId']]];
                  $graph_edges[] = ["data" => ["source" => $query, "target" => $entry['primaryAccession'], "label" => "protein", "type" => "biological"]];
              }
          } else {
              echo "<p>No UniProt results found.</p>";
          }
        ?>
      </div>

      <?php $graph_nodes[] = ["data" => ["id" => $query, "label" => $query]]; ?>

      <?php if (!empty($graph_nodes)): ?>
        <h3>🌐 Disease Graph Preview</h3>
        <div id="cy"></div>
        <div id="legend">
          <div class="legend-item"><div class="legend-line solid-blue"></div> Chemical (solid, blue)</div>
          <div class="legend-item"><div class="legend-line dashed-green"></div> Biological (dashed, green)</div>
          <div class="legend-item"><div class="legend-line dotted-red"></div> Literature (dotted, red)</div>
        </div>
        <script>
          var cy = cytoscape({
            container: document.getElementById('cy'),
            elements: <?= json_encode(array_merge($graph_nodes, $graph_edges)) ?>,
            style: [
              { selector: 'node', style: { 'content': 'data(label)', 'background-color': '#0f766e', 'color': '#fff', 'text-valign': 'center', 'text-halign': 'center', 'font-size': '12px', 'padding': '10px' }},
              { selector: 'edge[type="chemical"]', style: { 'line-style': 'solid', 'line-color': '#2563eb', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#2563eb', 'label': 'data(label)', 'font-size': '10px' }},
              { selector: 'edge[type="biological"]', style: { 'line-style': 'dashed', 'line-color': '#16a34a', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#16a34a', 'label': 'data(label)', 'font-size': '10px' }},
              { selector: 'edge[type="literature"]', style: { 'line-style': 'dotted', 'line-color': '#dc2626', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#dc2626', 'label': 'data(label)', 'font-size': '10px' }}
            ],
            layout: { name: 'cose' }
          });
        </script>
      <?php endif; ?>

    <?php else: ?>
      <!-- Molecule-focused -->
      <div class="box">
        <h3>🧪 PubChem</h3>
        <?php 
          $pc = fetch_pubchem($query);
          if ($pc && isset($pc['PropertyTable']['Properties'][0])) {
              $props = $pc['PropertyTable']['Properties'][0];
              echo "<p><b>Formula:</b> " . $props['MolecularFormula'] . "</p>";
              echo "<p><b>Weight:</b> " . $props['MolecularWeight'] . "</p>";
              echo "<p><b>InChIKey:</b> " . $props['InChIKey'] . "</p>";

              $graph_nodes[] = ["data" => ["id" => $query, "label" => $query]];
              $graph_nodes[] = ["data" => ["id" => $props['InChIKey'], "label" => "InChIKey"]];
              $graph_edges[] = ["data" => ["source" => $query, "target" => $props['InChIKey'], "label" => "has_key", "type" => "chemical"]];
          } else {
              echo "<p>No PubChem results found.</p>";
          }
        ?>
      </div>

      <div class="box">
        <h3>💊 ChEMBL</h3>
        <?php 
          $chembl = fetch_chembl($query);
          if ($chembl && isset($chembl['molecule_chembl_id'])) {
              echo "<p><b>ChEMBL ID:</b> " . htmlspecialchars($chembl['molecule_chembl_id']) . "</p>";
              echo "<p><b>Preferred Name:</b> " . htmlspecialchars($chembl['pref_name']) . "</p>";

              $graph_nodes[] = ["data" => ["id" => $chembl['molecule_chembl_id'], "label" => "ChEMBL"]];
              $graph_edges[] = ["data" => ["source" => $query, "target" => $chembl['molecule_chembl_id'], "label" => "mapped_to", "type" => "chemical"]];
          } else {
              echo "<p>No ChEMBL results found.</p>";
          }
        ?>
      </div>

      <?php if (!empty($graph_nodes)): ?>
        <h3>🌐 Molecular Graph Preview</h3>
        <div id="cy"></div>
        <div id="legend">
          <div class="legend-item"><div class="legend-line solid-blue"></div> Chemical (solid, blue)</div>
          <div class="legend-item"><div class="legend-line dashed-green"></div> Biological (dashed, green)</div>
          <div class="legend-item"><div class="legend-line dotted-red"></div> Literature (dotted, red)</div>
        </div>
        <script>
          var cy = cytoscape({
            container: document.getElementById('cy'),
            elements: <?= json_encode(array_merge($graph_nodes, $graph_edges)) ?>,
            style: [
              { selector: 'node', style: { 'content': 'data(label)', 'background-color': '#0f766e', 'color': '#fff', 'text-valign': 'center', 'text-halign': 'center', 'font-size': '12px', 'padding': '10px' }},
              { selector: 'edge[type="chemical"]', style: { 'line-style': 'solid', 'line-color': '#2563eb', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#2563eb', 'label': 'data(label)', 'font-size': '10px' }},
              { selector: 'edge[type="biological"]', style: { 'line-style': 'dashed', 'line-color': '#16a34a', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#16a34a', 'label': 'data(label)', 'font-size': '10px' }},
              { selector: 'edge[type="literature"]', style: { 'line-style': 'dotted', 'line-color': '#dc2626', 'target-arrow-shape': 'triangle', 'target-arrow-color': '#dc2626', 'label': 'data(label)', 'font-size': '10px' }}
            ],
            layout: { name: 'cose' }
          });
        </script>
      <?php endif; ?>

    <?php endif; ?>
  </div>
  <?php endif; ?>
</body>
</html>

