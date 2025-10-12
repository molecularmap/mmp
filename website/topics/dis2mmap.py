
#!/usr/bin/env python3
import json, os

# --- Configuration ---
DISEASE_FILE = "disease.json"
MOLECULARMAP_FILE = "molecularmap.json"
OUTPUT_FILE = "molecularmap_merged.json"

# --- Load JSON files ---
if not os.path.exists(DISEASE_FILE):
    raise FileNotFoundError(f"Missing {DISEASE_FILE}")
if not os.path.exists(MOLECULARMAP_FILE):
    # Create empty scaffold if needed
    molecularmap = {"nodes": [], "links": []}
else:
    with open(MOLECULARMAP_FILE, "r", encoding="utf-8") as f:
        molecularmap = json.load(f)

with open(DISEASE_FILE, "r", encoding="utf-8") as f:
    diseases = json.load(f)

# Convert existing nodes to dict for fast lookup
node_dict = {n["id"]: n for n in molecularmap.get("nodes", [])}
links = molecularmap.get("links", [])

def add_node(id, category):
    """Add node if not exists"""
    if id not in node_dict:
        node_dict[id] = {"id": id, "category": category}
        print(f"Added node: {id} ({category})")

def add_link(source, target, ltype="semantic"):
    """Add link if not duplicate"""
    if not any(l["source"] == source and l["target"] == target for l in links):
        links.append({"source": source, "target": target, "type": ltype})
        print(f"Linked {source} → {target}")

# --- Merge diseases into molecularmap ---
for d in diseases:
    dname = d["name"]
    add_node(dname, "disease")
    for mol in d.get("relatedMolecules", []):
        add_node(mol, "drug" if mol.lower() in d.get("drug","").lower() else "pathway")
        add_link(dname, mol, "disease-molecule")

# --- Finalize JSON ---
molecularmap_merged = {"nodes": list(node_dict.values()), "links": links}

with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
    json.dump(molecularmap_merged, f, indent=2)
print(f"\n✅ Merged molecular map written to {OUTPUT_FILE}")

