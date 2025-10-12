
<?php
  $assays = json_decode(file_get_contents('bioassay.json'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MolecularMap — Bioassay Explorer</title>
  <meta name="description" content="Interactive bioassay, biomarker, and diagnostic dataset explorer for MolecularMap.">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {font-family:'Segoe UI',system-ui,sans-serif;background:#fafafa;margin:0;color:#1e293b;}
    header {background:linear-gradient(135deg,#0f766e,#083344);color:white;padding:2.5rem;text-align:center;}
    main {max-width:1100px;margin:2rem auto;padding:0 1.5rem;}
    input[type=text]{width:100%;max-width:400px;padding:0.5rem 0.8rem;font-size:1rem;border:1px solid #ccc;border-radius:6px;margin-bottom:1.5rem;}
    table{width:100%;border-collapse:collapse;background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05);}
    th,td{padding:0.75rem 1rem;border-bottom:1px solid #e2e8f0;text-align:left;}
    th{background:#0f766e;color:white;text-transform:uppercase;letter-spacing:0.03em;}
    tr:hover{background:#f1f5f9;}
    a{color:#0f766e;text-decoration:none;cursor:pointer;}
    .btn{display:inline-block;background:#0f766e;color:white;padding:0.3rem 0.8rem;border-radius:6px;font-size:0.85rem;text-decoration:none;margin-right:0.3rem;transition:background 0.2s;}
    .btn:hover{background:#115e59;}
    footer{text-align:center;color:#64748b;padding:2rem;}
    #explorer{background:white;margin-top:2rem;padding:1.5rem;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.08);display:none;}
    #explorer h2{color:#0f766e;margin-top:0;}
    #explorer canvas{max-width:600px;margin:1rem auto;display:block;}
    .fade{animation:fadeIn 0.4s ease;}
    @keyframes fadeIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
  </style>
</head>
<body>

<header>
  <h1>🧪 Bioassay & Diagnostics Explorer</h1>
  <p>Interactive exploration of experimental and clinical assay data for AI integration.</p>
  <a href="index.html" style="color:#a0f0d0;text-decoration:none;">← Back to Home</a>
</header>

<main>
  <input type="text" id="searchBox" placeholder="🔍 Search assays..." onkeyup="filterTable()">

  <table id="assayTable">
    <thead>
      <tr><th>Dataset / Source</th><th>Category</th><th>Scope</th><th>Links</th></tr>
    </thead>
    <tbody>
      <?php foreach ($assays as $a): ?>
      <tr>
        <td><a href="#" onclick="showExplorer('<?php echo $a['id']; ?>');return false;"><strong><?php echo $a['title']; ?></strong></a></td>
        <td><?php echo $a['category']; ?></td>
        <td><?php echo $a['scope']; ?></td>
        <td>
          <?php foreach ($a['links'] as $link): ?>
            <a class="btn" href="<?php echo $link['url']; ?>" target="_blank"><?php echo $link['label']; ?></a>
          <?php endforeach; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div id="explorer" class="fade">
    <h2 id="explorerTitle"></h2>
    <p id="explorerDesc"></p>
    <canvas id="explorerChart"></canvas>
    <div id="explorerExtra"></div>
  </div>
</main>

<footer>MolecularMap — Unified Molecular Intelligence © 2014–2025</footer>

<script>
const assays = <?php echo json_encode($assays, JSON_PRETTY_PRINT); ?>;
let chart;

function filterTable() {
  const filter = document.getElementById('searchBox').value.toLowerCase();
  document.querySelectorAll('#assayTable tbody tr').forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
  });
}

function showExplorer(id) {
  const info = assays.find(a => a.id === id);
  if (!info) return;

  const explorer = document.getElementById('explorer');
  document.getElementById('explorerTitle').textContent = info.title;
  document.getElementById('explorerDesc').textContent = info.description;

  // Example simulated data visualization
  const ctx = document.getElementById('explorerChart').getContext('2d');
  if (chart) chart.destroy();

  const demoData = {
    PubChem: { labels: ["Active", "Inactive", "Inconclusive"], values: [62, 28, 10], colors: ["#0f766e", "#94a3b8", "#facc15"] },
    ATILA: { labels: ["Infectious", "Cancer", "Cardio", "Other"], values: [45, 30, 15, 10], colors: ["#10b981","#f43f5e","#3b82f6","#94a3b8"] },
    BAO: { labels: ["Cell-based", "Biochemical", "Imaging", "Binding"], values: [35, 40, 15, 10], colors: ["#14b8a6","#f97316","#6366f1","#a3a3a3"] },
    CTGOV: { labels: ["Active", "Completed", "Recruiting", "Suspended"], values: [25, 50, 20, 5], colors: ["#22d3ee","#10b981","#3b82f6","#f43f5e"] },
    HumanProteinAtlas: { labels: ["Tissue", "Single-Cell", "Cancer", "Brain"], values: [40, 25, 25, 10], colors: ["#f59e0b","#0ea5e9","#ef4444","#14b8a6"] },
    LDTs: { labels: ["Genetic", "Metabolic", "Immunoassay", "Other"], values: [30, 30, 25, 15], colors: ["#8b5cf6","#f97316","#10b981","#94a3b8"] }
  };

  const d = demoData[id] || demoData.PubChem;
  chart = new Chart(ctx, {
    type: 'doughnut',
    data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: d.colors }] },
    options: { plugins: { legend: { position: 'bottom' } } }
  });

  // Example of extended info block
  document.getElementById('explorerExtra').innerHTML = `
    <p><strong>Data Volume:</strong> ${info.size}</p>
    <p><strong>Applications:</strong> ${info.use}</p>
    <p><strong>Example entries:</strong></p>
    <ul>
      ${id === 'PubChem' ? `
        <li>BioAssay AID: <code>504492</code> — Kinase inhibitor screen</li>
        <li>BioAssay AID: <code>225743</code> — Antiviral activity study</li>` :
      id === 'ATILA' ? `
        <li>COVID-19 qPCR Kit — CE-marked clinical test</li>
        <li>BRCA Mutation Detection Panel</li>` :
      id === 'CTGOV' ? `
        <li>NCT01234567 — Oncology diagnostic biomarker study</li>
        <li>NCT04567890 — Cardiovascular imaging diagnostic trial</li>` :
      `<li>Data examples vary by dataset.</li>`}
    </ul>
  `;

  explorer.style.display = "block";
  explorer.scrollIntoView({ behavior: "smooth" });
}
</script>

</body>
</html>

