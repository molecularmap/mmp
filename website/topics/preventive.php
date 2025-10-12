
<script>
let chart, molecules=[];

fetch("preventive.json")
  .then(r=>r.json())
  .then(data=>{
    molecules=data;
    const tbody=document.querySelector("#molTable tbody");
    data.forEach(m=>{
      const row=document.createElement("tr");
      row.innerHTML=`
        <td><a href="#" onclick="showExplorer('${m.id}');return false;"><strong>${m.name}</strong></a></td>
        <td>${m.category}</td>
        <td>${m.effect}</td>
        <td>${m.links.map(l=>`<a class='btn' href='${l.url}' target='_blank'>${l.label}</a>`).join(" ")}</td>`;
      tbody.appendChild(row);
    });
  });

function filterTable(){
  const f=document.getElementById("searchBox").value.toLowerCase();
  document.querySelectorAll("#molTable tbody tr").forEach(r=>{
    r.style.display=r.innerText.toLowerCase().includes(f)?'':'none';
  });
}

async function showExplorer(id){
  const m=molecules.find(x=>x.id===id);
  if(!m) return;

  const exp=document.getElementById('explorer');
  exp.style.display="block";
  document.getElementById('explorerTitle').textContent=m.name;
  document.getElementById('explorerDesc').textContent=m.description;
  document.getElementById('explorerExtra').innerHTML=`
    <p><strong>Biological Target:</strong> ${m.target}</p>
    <p><strong>Pathways:</strong> ${m.pathways.join(', ')}</p>
    <p><strong>Health Focus:</strong> ${m.health.join(', ')}</p>
    <div id="liveData"><em>Loading live data...</em></div>`;

  // Draw chart
  const ctx=document.getElementById('explorerChart').getContext('2d');
  if(chart) chart.destroy();
  const labels=["Oxidative Stress","Metabolism","Inflammation","Longevity"];
  const colors=["#16a34a","#3b82f6","#f97316","#a855f7"];
  chart=new Chart(ctx,{type:'doughnut',data:{labels,datasets:[{data:m.profile,backgroundColor:colors}]},
    options:{plugins:{legend:{position:'bottom'}}}});

  drawGraph(m.graph.nodes,m.graph.links);
  exp.scrollIntoView({behavior:"smooth"});

  // Fetch live data concurrently
  const live = await fetchLiveData(m.name);
  document.getElementById("liveData").innerHTML = live;
}

function drawGraph(nodes,links){
  const svg=d3.select("#graph").html("").append("svg")
    .attr("width","100%").attr("height",420);
  const width=document.getElementById("graph").clientWidth, height=420;
  const sim=d3.forceSimulation(nodes)
    .force("link",d3.forceLink(links).id(d=>d.id).distance(100))
    .force("charge",d3.forceManyBody().strength(-250))
    .force("center",d3.forceCenter(width/2,height/2));

  const link=svg.append("g").selectAll("line").data(links).enter().append("line")
    .attr("stroke","#9ca3af").attr("stroke-width",1.5);
  const node=svg.append("g").selectAll("circle").data(nodes).enter().append("circle")
    .attr("r",8).attr("fill",d=>["#15803d","#22c55e","#84cc16","#4ade80"][d.group%4])
    .call(drag(sim));
  const label=svg.append("g").selectAll("text").data(nodes).enter().append("text")
    .attr("font-size","11px").attr("dy",-12).attr("text-anchor","middle").text(d=>d.id);

  sim.on("tick",()=>{
    link.attr("x1",d=>d.source.x).attr("y1",d=>d.source.y)
        .attr("x2",d=>d.target.x).attr("y2",d=>d.target.y);
    node.attr("cx",d=>d.x).attr("cy",d=>d.y);
    label.attr("x",d=>d.x).attr("y",d=>d.y);
  });

  function drag(sim){
    return d3.drag()
      .on("start",(e,d)=>{if(!e.active)sim.alphaTarget(0.3).restart();d.fx=d.x;d.fy=d.y;})
      .on("drag",(e,d)=>{d.fx=e.x;d.fy=e.y;})
      .on("end",(e,d)=>{if(!e.active)sim.alphaTarget(0);d.fx=null;d.fy=null;});
  }
}

// ---------------- LIVE DATA FUNCTIONS ----------------
async function fetchLiveData(molecule){
  try {
    const [fooddb, hmdb, kegg] = await Promise.all([
      fetchFoodDB(molecule),
      fetchHMDB(molecule),
      fetchKEGG(molecule)
    ]);
    return `
      <h3>🌾 Real-World Sources</h3><p>${fooddb}</p>
      <h3>🧬 Metabolomic Context</h3><p>${hmdb}</p>
      <h3>🧫 Biological Pathways</h3><p>${kegg}</p>`;
  } catch (e) {
    console.warn("Live fetch failed", e);
    return "<p><em>Could not retrieve live data — showing cached information.</em></p>";
  }
}

async function fetchFoodDB(name){
  const query = encodeURIComponent(name);
  const url = `https://foodb.ca/compounds.json?search=${query}`;
  const res = await fetch(url);
  if (!res.ok) throw "FoodDB fetch failed";
  const data = await res.json();
  if (data.length === 0) return "No food sources found.";
  const foods = data.slice(0,3).map(f=>f.name).join(", ");
  return `${name} is found in: <strong>${foods}</strong>`;
}

async function fetchHMDB(name){
  const url = `https://hmdb.ca/unearth/q?utf8=✓&query=${encodeURIComponent(name)}`;
  const res = await fetch(url);
  if (!res.ok) throw "HMDB fetch failed";
  const text = await res.text();
  const snippet = text.replace(/<[^>]+>/g,'').slice(0,200);
  return `HMDB match: ${snippet}...`;
}

async function fetchKEGG(name){
  const url = `https://rest.kegg.jp/find/pathway/${encodeURIComponent(name)}`;
  const res = await fetch(url);
  if (!res.ok) throw "KEGG fetch failed";
  const text = await res.text();
  const rows = text.split("\n").filter(r=>r.includes("path:")).slice(0,3);
  return rows.length>0 ? rows.map(r=>r.split("\t")[1]).join("; ") : "No KEGG pathways found.";
}
</script>

