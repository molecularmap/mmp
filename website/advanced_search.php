<?php
/**
 * MolecularMap Advanced AI Search
 * Filename: search_advanced.php
 * 
 * Intelligent search system with specialized AI agents
 */

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$agent = isset($_GET['agent']) ? $_GET['agent'] : 'molecule';

// AI Agent Configurations
$ai_agents = [
    'molecule' => [
        'name' => 'Molecule Explorer',
        'icon' => '🔬',
        'description' => 'Deep molecular analysis with structure, properties, and interactions',
        'color' => '#818cf8'
    ],
    'drug' => [
        'name' => 'Drug Discovery Agent',
        'icon' => '💊',
        'description' => 'Find similar drugs, clinical trials, and therapeutic applications',
        'color' => '#f87171'
    ],
    'disease' => [
        'name' => 'Disease Pathway Finder',
        'icon' => '🧬',
        'description' => 'Map disease mechanisms, biomarkers, and molecular pathways',
        'color' => '#4ade80'
    ],
    'patent' => [
        'name' => 'Innovation Finder',
        'icon' => '💡',
        'description' => 'Search patents, publications, and research breakthroughs',
        'color' => '#fbbf24'
    ],
    'target' => [
        'name' => 'Target Predictor',
        'icon' => '🎯',
        'description' => 'Predict protein targets and binding affinity',
        'color' => '#a78bfa'
    ],
    'natural' => [
        'name' => 'Natural Compound Hunter',
        'icon' => '🌿',
        'description' => 'Find natural alternatives and plant-derived molecules',
        'color' => '#10b981'
    ]
];

// Smart Query Analyzer
function analyze_query($query) {
    $query_lower = strtolower($query);
    
    // Detect query type
    $types = [];
    
    // Check for SMILES pattern
    if (preg_match('/^[A-Z0-9\(\)\[\]=#@\+\-\\\\\/]+$/i', $query) && strlen($query) > 5) {
        $types[] = 'smiles';
    }
    
    // Check for InChI
    if (strpos($query, 'InChI=') === 0) {
        $types[] = 'inchi';
    }
    
    // Check for disease keywords
    $disease_keywords = ['cancer', 'diabetes', 'alzheimer', 'parkinson', 'disease', 'syndrome', 'disorder'];
    foreach ($disease_keywords as $kw) {
        if (strpos($query_lower, $kw) !== false) {
            $types[] = 'disease';
            break;
        }
    }
    
    // Check for drug keywords
    $drug_keywords = ['drug', 'medication', 'treatment', 'therapy', 'pharmaceutical'];
    foreach ($drug_keywords as $kw) {
        if (strpos($query_lower, $kw) !== false) {
            $types[] = 'drug';
            break;
        }
    }
    
    // Check for gene/protein
    if (preg_match('/^[A-Z0-9]{2,10}$/', $query) || strpos($query_lower, 'protein') !== false) {
        $types[] = 'protein';
    }
    
    // Default to molecule
    if (empty($types)) {
        $types[] = 'molecule';
    }
    
    return $types;
}

// Enhanced API fetchers with error handling
function fetch_pubchem_enhanced($query) {
    $endpoints = [
        'compound/name/' . urlencode($query),
        'compound/cid/' . urlencode($query),
    ];
    
    foreach ($endpoints as $endpoint) {
        $url = "https://pubchem.ncbi.nlm.nih.gov/rest/pug/{$endpoint}/property/MolecularFormula,MolecularWeight,InChIKey,IUPACName,CanonicalSMILES/JSON";
        $resp = @file_get_contents($url);
        if ($resp) {
            return json_decode($resp, true);
        }
    }
    return null;
}

function fetch_chembl_enhanced($query) {
    $url = "https://www.ebi.ac.uk/chembl/api/data/molecule/search.json?q=" . urlencode($query) . "&limit=5";
    $resp = @file_get_contents($url);
    return $resp ? json_decode($resp, true) : null;
}

function fetch_drugbank_similar($query) {
    // Note: DrugBank requires API key for full access
    // This is a placeholder - implement with your API key
    return [
        'similar_drugs' => [],
        'message' => 'DrugBank integration available with API key'
    ];
}

function fetch_patents($query) {
    // USPTO or Google Patents API
    // Placeholder implementation
    $url = "https://api.patentsview.org/patents/query?q={\"_text_any\":{\"patent_abstract\":\"" . urlencode($query) . "\"}}&f=[\"patent_number\",\"patent_title\",\"patent_date\"]";
    $resp = @file_get_contents($url);
    return $resp ? json_decode($resp, true) : null;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MolecularMap - Advanced AI Search</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      color: #e2e8f0;
      min-height: 100vh;
    }

    .header {
      background: rgba(15, 23, 42, 0.8);
      backdrop-filter: blur(20px);
      padding: 1.5rem 2rem;
      border-bottom: 1px solid rgba(129, 140, 248, 0.2);
    }

    .header-content {
      max-width: 1400px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .logo {
      font-size: 1.5rem;
      font-weight: 700;
      background: linear-gradient(135deg, #c7d2fe, #818cf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-decoration: none;
    }

    .nav-links a {
      color: #cbd5e1;
      text-decoration: none;
      margin-left: 2rem;
      font-weight: 500;
      transition: color 0.2s;
    }

    .nav-links a:hover {
      color: #c7d2fe;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 3rem 2rem;
    }

    .search-section {
      background: rgba(30, 41, 59, 0.6);
      border: 1px solid rgba(129, 140, 248, 0.3);
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
      backdrop-filter: blur(20px);
    }

    .search-box-container {
      display: flex;
      gap: 1rem;
      margin-bottom: 1.5rem;
    }

    .search-box {
      flex: 1;
      display: flex;
      background: rgba(15, 23, 42, 0.8);
      border: 1px solid rgba(148, 163, 184, 0.3);
      border-radius: 12px;
      overflow: hidden;
    }

    .search-box input {
      flex: 1;
      padding: 1rem 1.5rem;
      border: none;
      background: transparent;
      color: #e2e8f0;
      font-size: 1rem;
      outline: none;
    }

    .search-box input::placeholder {
      color: #64748b;
    }

    .search-box button {
      background: linear-gradient(135deg, #8b5cf6, #6366f1);
      color: white;
      border: none;
      padding: 0 2rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s;
    }

    .search-box button:hover {
      background: linear-gradient(135deg, #7c3aed, #4f46e5);
    }

    .query-insights {
      display: flex;
      gap: 0.5rem;
      flex-wrap: wrap;
    }

    .insight-badge {
      background: rgba(129, 140, 248, 0.2);
      border: 1px solid rgba(129, 140, 248, 0.4);
      padding: 0.4rem 0.8rem;
      border-radius: 20px;
      font-size: 0.85rem;
      color: #c7d2fe;
    }

    .ai-agents {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .agent-card {
      background: rgba(30, 41, 59, 0.6);
      border: 2px solid rgba(148, 163, 184, 0.2);
      border-radius: 16px;
      padding: 1.5rem;
      cursor: pointer;
      transition: all 0.3s;
      backdrop-filter: blur(20px);
    }

    .agent-card:hover {
      transform: translateY(-5px);
      border-color: rgba(129, 140, 248, 0.5);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .agent-card.active {
      border-color: #818cf8;
      background: rgba(129, 140, 248, 0.1);
    }

    .agent-icon {
      font-size: 2.5rem;
      margin-bottom: 0.8rem;
    }

    .agent-name {
      font-size: 1.2rem;
      font-weight: 600;
      color: #c7d2fe;
      margin-bottom: 0.5rem;
    }

    .agent-description {
      font-size: 0.9rem;
      color: #94a3b8;
      line-height: 1.5;
    }

    .results-section {
      background: rgba(30, 41, 59, 0.4);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 16px;
      padding: 2rem;
      backdrop-filter: blur(20px);
    }

    .result-card {
      background: rgba(15, 23, 42, 0.6);
      border: 1px solid rgba(148, 163, 184, 0.2);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .result-card h3 {
      color: #c7d2fe;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .result-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
    }

    .data-item {
      background: rgba(30, 41, 59, 0.4);
      padding: 0.8rem;
      border-radius: 8px;
      border: 1px solid rgba(148, 163, 184, 0.1);
    }

    .data-label {
      font-size: 0.8rem;
      color: #94a3b8;
      text-transform: uppercase;
      margin-bottom: 0.3rem;
    }

    .data-value {
      color: #e2e8f0;
      font-weight: 500;
    }

    .ai-insight {
      background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(99, 102, 241, 0.1));
      border: 1px solid rgba(139, 92, 246, 0.3);
      border-radius: 12px;
      padding: 1.5rem;
      margin-top: 1.5rem;
    }

    .ai-insight h4 {
      color: #c7d2fe;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .insight-list {
      list-style: none;
      padding: 0;
    }

    .insight-list li {
      padding: 0.5rem 0;
      color: #cbd5e1;
      padding-left: 1.5rem;
      position: relative;
    }

    .insight-list li:before {
      content: '→';
      position: absolute;
      left: 0;
      color: #818cf8;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <div class="header">
    <div class="header-content">
      <a href="index.html" class="logo">🧬 MolecularMap</a>
      <div class="nav-links">
        <a href="index.html">Home</a>
        <a href="search.php">Basic Search</a>
        <a href="search_advanced.php">Advanced AI Search</a>
      </div>
    </div>
  </div>

  <!-- Main Container -->
  <div class="container">
    
    <!-- Search Section -->
    <div class="search-section">
      <h1 style="color: #c7d2fe; margin-bottom: 1.5rem;">🤖 Advanced AI Search</h1>
      
      <form method="GET" action="search_advanced.php">
        <div class="search-box-container">
          <div class="search-box">
            <input type="text" 
                   name="q" 
                   value="<?= htmlspecialchars($query) ?>" 
                   placeholder="Enter molecule name, SMILES, disease, gene, or natural language query..."
                   required>
            <button type="submit">🔍 Search</button>
          </div>
        </div>
        
        <?php if ($query): 
          $query_types = analyze_query($query);
        ?>
        <div class="query-insights">
          <span style="color: #94a3b8; font-size: 0.9rem;">Detected query types:</span>
          <?php foreach ($query_types as $type): ?>
            <span class="insight-badge"><?= ucfirst($type) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </form>
    </div>

    <!-- AI Agents Grid -->
    <h2 style="color: #c7d2fe; margin-bottom: 1.5rem;">Select AI Agent</h2>
    <div class="ai-agents">
      <?php foreach ($ai_agents as $key => $agent_info): ?>
        <a href="?q=<?= urlencode($query) ?>&agent=<?= $key ?>" style="text-decoration: none;">
          <div class="agent-card <?= $agent === $key ? 'active' : '' ?>">
            <div class="agent-icon"><?= $agent_info['icon'] ?></div>
            <div class="agent-name"><?= $agent_info['name'] ?></div>
            <div class="agent-description"><?= $agent_info['description'] ?></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- Results Section -->
    <?php if ($query): ?>
    <div class="results-section">
      <h2 style="color: #c7d2fe; margin-bottom: 1.5rem;">
        <?= $ai_agents[$agent]['icon'] ?> 
        Results from <?= $ai_agents[$agent]['name'] ?>
      </h2>

      <?php
      // Route to appropriate agent
      switch ($agent) {
        case 'molecule':
          $pubchem = fetch_pubchem_enhanced($query);
          if ($pubchem && isset($pubchem['PropertyTable']['Properties'][0])):
            $props = $pubchem['PropertyTable']['Properties'][0];
      ?>
          <div class="result-card">
            <h3>🔬 Molecular Properties</h3>
            <div class="result-grid">
              <div class="data-item">
                <div class="data-label">Molecular Formula</div>
                <div class="data-value"><?= $props['MolecularFormula'] ?? 'N/A' ?></div>
              </div>
              <div class="data-item">
                <div class="data-label">Molecular Weight</div>
                <div class="data-value"><?= $props['MolecularWeight'] ?? 'N/A' ?> g/mol</div>
              </div>
              <div class="data-item">
                <div class="data-label">IUPAC Name</div>
                <div class="data-value"><?= $props['IUPACName'] ?? 'N/A' ?></div>
              </div>
              <div class="data-item">
                <div class="data-label">SMILES</div>
                <div class="data-value" style="font-family: monospace; font-size: 0.85rem;"><?= $props['CanonicalSMILES'] ?? 'N/A' ?></div>
              </div>
            </div>
          </div>

          <div class="ai-insight">
            <h4>🤖 AI Insights</h4>
            <ul class="insight-list">
              <li>Molecular complexity suggests <?= strlen($props['CanonicalSMILES'] ?? '') > 50 ? 'complex' : 'simple' ?> structure</li>
              <li>Weight range indicates <?= ($props['MolecularWeight'] ?? 0) > 500 ? 'large' : 'small' ?> molecule classification</li>
              <li>Suitable for <?= ($props['MolecularWeight'] ?? 0) < 500 ? 'oral bioavailability (Lipinski\'s Rule)' : 'specialized delivery methods' ?></li>
            </ul>
          </div>
      <?php
          endif;
          break;

        case 'patent':
          $patents = fetch_patents($query);
      ?>
          <div class="result-card">
            <h3>💡 Patent Search Results</h3>
            <p style="color: #94a3b8;">Integration with USPTO and Google Patents API</p>
            <div class="ai-insight" style="margin-top: 1rem;">
              <h4>🤖 Innovation Finder</h4>
              <p style="color: #cbd5e1;">
                This agent will search across:
              </p>
              <ul class="insight-list">
                <li>USPTO patent database</li>
                <li>Google Patents</li>
                <li>European Patent Office</li>
                <li>Scientific publications (PubMed, arXiv)</li>
                <li>Clinical trial registries</li>
              </ul>
            </div>
          </div>
      <?php
          break;

        default:
          ?>
          <div class="result-card">
            <h3><?= $ai_agents[$agent]['icon'] ?> <?= $ai_agents[$agent]['name'] ?></h3>
            <p style="color: #94a3b8; margin-bottom: 1rem;">
              <?= $ai_agents[$agent]['description'] ?>
            </p>
            <div class="ai-insight">
              <h4>🤖 Agent Capabilities</h4>
              <p style="color: #cbd5e1; margin-bottom: 1rem;">
                This specialized AI agent will analyze your query and provide:
              </p>
              <ul class="insight-list">
                <li>Multi-database cross-referencing</li>
                <li>Semantic similarity search</li>
                <li>Predictive analytics</li>
                <li>Literature mining and synthesis</li>
                <li>Real-time data integration</li>
              </ul>
            </div>
          </div>
          <?php
      }
      ?>

    </div>
    <?php endif; ?>

  </div>

</body>
</html>