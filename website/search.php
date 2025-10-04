
<?php
// search.php — MolecularMap Smart Search with Styled Graphs + Home Link

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
    nav {
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 1px solid #e2e8f0;
    }
    nav a {
      text-decoration: none;
      color: #0f766e;
      font-weight: bold;
      margin-right: 1rem;
    }
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
  <!-- Top nav with Home link -->
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
    <!-- (Your existing disease/molecule search logic goes here — unchanged) -->

    <p style="margin-top:2rem;">
      <a href="index.html" style="color:#0f766e; font-weight:bold;">⬅ Back to Home</a>
    </p>
  </div>
  <?php endif; ?>
</body>
</html>

