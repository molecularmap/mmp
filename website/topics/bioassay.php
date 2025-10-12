
<?php
  $assays = json_decode(file_get_contents('bioassay.json'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>MolecularMap — Bioassay Explorer</title>
  <meta name="description" content="AI-integrated explorer of bioassay, omics, and diagnostic data.">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://d3js.org/d3.v7.min.js"></script>
  <style>
    body {font-family:'Segoe UI',system-ui,sans-serif;background:#fafafa;margin:0;color:#1e293b;}
    header {background:linear-gradient(135deg,#0f766e,#083344);color:white;padding:2.5rem;text-align:center;}
    main {max-width:1200px;margin:2rem auto;padding:0 1.5rem;}
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
    #chart-panel{display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-start;}
    canvas{flex:1 1 400px;max-width:500px;}
    #graph{flex:1 1 400px;height:420px;border:1px solid #e5e7eb;border-radius:8px;}
  </style>
</head>
<body>

<header>
  <h1>🧪 Bioassay & Diagnostics Explorer</h1>
  <p>Interactive visualization of molecular, bioassay, omics, and diagnostic datasets.</p>
  <a href="index.html" style="color:#a0f0d0;text-decoration:none;">← Back to Home</a>
</header>

<main>
  <input type="text" id="searchBox" placeholder="🔍 Search datasets..." onkeyup="filterTable()">

  <table id="assayTable">
    <thead><tr><th>Dataset / Source</th><th>Category</th><th>Scope</th><th>Links</th></tr></thead>
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

  <div id="explorer">
    <h2 id="explorerTitle"></h2>
    <p id="explorerDesc"></p>
    <div id="chart-panel">
      <canvas id="explorerChart"></canvas>
      <div id="graph"></div>
    </div>
    <div id="explorerExtra"></div>
  </div>
</main>

<footer>MolecularMap — Unified Molecular Intelligence © 2014–2025</footer>

<script>
const assays = <?php echo json_encode($assays, JSON_PRETTY_PRINT); ?>;
let chart;

function filterTable() {
  const f = document.getElementById('searchBox').value.toLowerCase();
  document.querySelectorAll('#assayTable tbody tr').forEach(r => {
    r.style.display = r.innerText.toLowerCase().includes(f) ? '' : 'none';
  });
}

async function showExplorer(id) {
  const info = assays.find(a => a.id === id);
  if (!info) return;

  const exp = document.getElementById('explorer');
  exp.style.display = "block";
  document.getElementById('explorerTitle').textContent = info.title;
  document.getElementById('explorerDesc').textContent = info.description;
  document.getElementById('explorerExtra').innerHTML =
    `<p><strong>Data Volume:</strong> ${info.size}</p><p><strong>Applications:</strong> ${info.use}</p>`;

  const ctx = document.getElementById('explorerChart').getContext('2d');
  if (chart) chart.destroy();

  let labels=[], values=[], colors=[], nodes=[], links=[];

  try {
    switch(id) {
      case "PubChem":
        labels=["Active","Inactive","Inconclusive"];
        values=[62,28,10];
        colors=["#0f766e","#94a3b8","#facc15"];
        nodes=[{id:"AID:504492",group:1},{id:"Kinase",group:2},{id:"Drug Discovery",group:3}];
        links=[{source:"AID:504492",target:"Kinase"},{source:"Kinase",target:"Drug Discovery"}];
        break;
      case "ChEMBL":
        labels=["Binding","Functional","ADME","Toxicity"];
        values=[45,25,20,10];
        colors=["#10b981","#3b82f6","#f97316","#a855f7"];
        nodes=[
          {id:"CHEMBL25",group:1},{id:"CHEMBL190",group:1},
          {id:"Target: EGFR",group:2},{id:"Target: CYP3A4",group:2},
          {id:"Drug Discovery",group:3}
        ];
        links=[
          {source:"CHEMBL25",target:"Target: EGFR"},
          {source:"CHEMBL190",target:"Target: CYP3A4"},
          {source:"Target: EGFR",target:"Drug Discovery"},
          {source:"Target: CYP3A4",target:"Drug Discovery"}
        ];
        break;
      case "MassIVE":
        labels=["Proteomics","Metabolomics","Lipidomics"];
        values=[45,35,20];
        colors=["#06b6d4","#f97316","#84cc16"];
        nodes=[
          {id:"MSV000089123",group:1},{id:"MSV000088888",group:1},
          {id:"Protein",group:2},{id:"Metabolite",group:2},
          {id:"Pathway",group:3}
        ];
        links=[
          {source:"MSV000089123",target:"Protein"},
          {source:"MSV000088888",target:"Metabolite"},
          {source:"Protein",target:"Pathway"},
          {source:"Metabolite",target:"Pathway"}
        ];
        break;
      case "HPA":
        labels=["Tissue","Cancer","Brain","Single-Cell"];
        values=[40,25,20,15];
        colors=["#f59e0b","#ef4444","#0ea5e9","#14b8a6"];
        nodes=[
          {id:"TP53",group:1},{id:"EGFR",group:1},
          {id:"Tissue",group:2},{id:"Cancer",group:2},{id:"Atlas",group:3}
        ];
        links=[
          {source:"TP53",target:"Cancer"},
          {source:"EGFR",target:"Tissue"},
          {source:"Cancer",target:"Atlas"},
          {source:"Tissue",target:"Atlas"}
        ];
        break;
      case "ATILA":
        labels=["Infectious","Cancer","Cardio","Other"];
        values=[46,30,14,10];
        colors=["#10b981","#ef4444","#3b82f6","#94a3b8"];
        nodes=[
          {id:"COVID-19 qPCR",group:1},{id:"BRCA Panel",group:1},
          {id:"Infection",group:2},{id:"Oncology",group:2},
          {id:"Diagnostics",group:3}
        ];
        links=[
          {source:"COVID-19 qPCR",target:"Infection"},
          {source:"BRCA Panel",target:"Oncology"},
          {source:"Infection",target:"Diagnostics"},
          {source:"Oncology",target:"Diagnostics"}
        ];
        break;
      case "CTGOV":
        labels=["Active","Completed","Recruiting","Suspended"];
        values=[25,50,20,5];
        colors=["#22d3ee","#10b981","#3b82f6","#f43f5e"];
        nodes=[
          {id:"NCT04567890",group:1},{id:"NCT01234567",group:1},
          {id:"Oncology",group:2},{id:"Cardio",group:2},
          {id:"Clinical Outcome",group:3}
        ];
        links=[
          {source:"NCT04567890",target:"Oncology"},
          {source:"NCT01234567",target:"Cardio"},
          {source:"Oncology",target:"Clinical Outcome"},
          {source:"Cardio",target:"Clinical Outcome"}
        ];
        break;
    }
  } catch (e) { console.warn("Error generating data", e); }

  chart = new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data: values, backgroundColor: colors }] },
    options: { plugins: { legend: { position: 'bottom' } } }
  });

  drawGraph(nodes, links);
  exp.scrollIntoView({ behavior: "smooth" });
}

// D3 Graph Renderer
function drawGraph(nodes, links) {
  const svg = d3.select("#graph").html("").append("svg")
    .attr("width","100%").attr("height",420);
  const width = document.getElementById("graph").clientWidth, height=420;

  const simulation = d3.forceSimulation(nodes)
    .force("link", d3.forceLink(links).id(d=>d.id).distance(100))
    .force("charge", d3.forceManyBody().strength(-250))
    .force("center", d3.forceCenter(width/2,height/2));

  const link = svg.append("g").selectAll("line").data(links)
    .enter().append("line").attr("stroke","#aaa").attr("stroke-width",1.5);

  const node = svg.append("g").selectAll("circle").data(nodes)
    .enter().append("circle")
    .attr("r",8)
    .attr("fill",d=>["#0ea5e9","#10b981","#f59e0b","#ef4444"][d.group%4])
    .call(drag(simulation));

  const label = svg.append("g").selectAll("text").data(nodes)
    .enter().append("text")
    .attr("font-size","11px").attr("dy",-12).attr("text-anchor","middle")
    .text(d=>d.id);

  simulation.on("tick",()=>{
    link.attr("x1",d=>d.source.x).attr("y1",d=>d.source.y)
        .attr("x2",d=>d.target.x).attr("y2",d=>d.target.y);
    node.attr("cx",d=>d.x).attr("cy",d=>d.y);
    label.attr("x",d=>d.x).attr("y",d=>d.y);
  });

  function drag(sim) {
    return d3.drag()
      .on("start",(e,d)=>{if(!e.active)sim.alphaTarget(0.3).restart();d.fx=d.x;d.fy=d.y;})
      .on("drag",(e,d)=>{d.fx=e.x;d.fy=e.y;})
      .on("end",(e,d)=>{if(!e.active)sim.alphaTarget(0);d.fx=null;d.fy=null;});
  }
}
</script>

</body>
</html>

