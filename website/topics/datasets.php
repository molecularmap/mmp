
<?php
  $datasets = json_decode(file_get_contents('datasets.json'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MolecularMap — Curated AI Datasets</title>
  <meta name="description" content="Curated molecular datasets for AI/ML research.">
  <style>
    body {font-family:'Segoe UI',system-ui,sans-serif;background:#fafafa;margin:0;color:#1e293b;}
    header {background:linear-gradient(135deg,#0f766e,#083344);color:white;padding:2.5rem;text-align:center;}
    main {max-width:1000px;margin:2rem auto;padding:0 1.5rem;}
    table {width:100%;border-collapse:collapse;background:white;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.05);}
    th,td {padding:0.75rem 1rem;border-bottom:1px solid #e2e8f0;text-align:left;}
    th {background:#0f766e;color:white;text-transform:uppercase;letter-spacing:0.03em;}
    tr:hover {background:#f1f5f9;}
    .btn {display:inline-block;background:#0f766e;color:white;padding:0.3rem 0.8rem;border-radius:6px;font-size:0.85rem;text-decoration:none;margin-right:0.3rem;transition:background 0.2s;}
    .btn:hover {background:#115e59;}
    input[type=text]{width:100%;max-width:400px;padding:0.5rem 0.8rem;font-size:1rem;border:1px solid #ccc;border-radius:6px;margin-bottom:1.5rem;}
    footer {text-align:center;color:#64748b;padding:2rem;}
    .modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background:rgba(0,0,0,0.5);}
    .modal-content{background:white;margin:8% auto;padding:2rem;border-radius:10px;max-width:600px;position:relative;box-shadow:0 4px 12px rgba(0,0,0,0.2);}
    .close{position:absolute;top:10px;right:14px;color:#555;font-size:22px;cursor:pointer;}
  </style>
</head>
<body>

<header>
  <h1>🧬 Curated AI-Ready Datasets</h1>
  <p>Explore standardized molecular datasets dynamically loaded from JSON.</p>
  <a href="index.html" style="color:#a0f0d0;text-decoration:none;">← Back to Home</a>
</header>

<main>
  <input type="text" id="searchBox" placeholder="🔍 Search datasets..." onkeyup="filterTable()">

  <table id="datasetTable">
    <thead>
      <tr>
        <th>Dataset</th><th>Source</th><th>Type</th><th>Format</th><th>Links</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($datasets as $d): ?>
      <tr>
        <td><a href="#" onclick="openModal('<?php echo $d['id']; ?>');return false;"><strong><?php echo $d['title']; ?></strong></a></td>
        <td><?php echo $d['source']; ?></td>
        <td><?php echo $d['type']; ?></td>
        <td><?php echo $d['format']; ?></td>
        <td>
          <?php if (!empty($d['download'])): ?>
            <a class="btn" href="<?php echo $d['download']; ?>" target="_blank">Download</a>
          <?php endif; ?>
          <?php if (!empty($d['api'])): ?>
            <a class="btn" href="<?php echo $d['api']; ?>" target="_blank">API</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>

<footer>MolecularMap — Unified Molecular Intelligence © 2014–2025</footer>

<div id="modal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h3 id="modalTitle"></h3>
    <p id="modalDescription"></p>
    <p><strong>Data Size:</strong> <span id="modalSize"></span></p>
    <p><strong>Applications:</strong> <span id="modalUse"></span></p>
  </div>
</div>

<script>
const datasets = <?php echo json_encode($datasets, JSON_PRETTY_PRINT); ?>;

function filterTable() {
  const input = document.getElementById('searchBox');
  const filter = input.value.toLowerCase();
  document.querySelectorAll('#datasetTable tbody tr').forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
  });
}

function openModal(id) {
  const info = datasets.find(d => d.id === id);
  if (info) {
    document.getElementById('modalTitle').textContent = info.title;
    document.getElementById('modalDescription').textContent = info.description;
    document.getElementById('modalSize').textContent = info.size;
    document.getElementById('modalUse').textContent = info.use;
    document.getElementById('modal').style.display = "block";
  }
}
function closeModal() { document.getElementById('modal').style.display = "none"; }
window.onclick = function(e){if(e.target==document.getElementById('modal'))closeModal();}
</script>

</body>
</html>

