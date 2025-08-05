import requests
import csv
import math

BASE_URL = (
    "https://www.rkguns.com/on/demandware.store/"
    "Sites-rkguns-Site/default/Search-UpdateGrid"
)
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]

def make_session():
    s = requests.Session()
    s.headers.update({
        "User-Agent": "Mozilla/5.0 (compatible; InventoryScraper/1.0)",
        "Accept": "application/json, text/javascript, */*; q=0.01",
        "X-Requested-With": "XMLHttpRequest",
        "Referer": "https://www.rkguns.com/firearms.html?page=1&numResults=36"
    })
    # Bypass age-gate
    s.cookies.set("hasVerifiedAge", "true", domain="www.rkguns.com")
    return s

def fetch_page(session, offset):
    resp = session.get(BASE_URL, params={
        "cgid": "firearms",
        "start": offset,
        "sz": 36,
        "format": "ajax"           # <— ensure AJAX format
    })
    resp.raise_for_status()
    return resp.json()

def main():
    sess = make_session()
    first = fetch_page(sess, 0)
    total = first["grid"]["hits"]
    pages = math.ceil(total / 36)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as fp:
        writer = csv.DictWriter(fp, fieldnames=FIELDS)
        writer.writeheader()

        for i in range(pages):
            offset = i * 36
            print(f"Fetching items {offset+1}–{min(offset+36, total)} of {total}", flush=True)
            data = fetch_page(sess, offset)
            for prod in data["grid"]["products"]:
                brand = prod.get("brand", "").strip()
                name = (
                    prod.get("pageTitle","").strip()
                    or prod.get("productName","").strip()
                    or prod.get("name","").strip()
                )
                writer.writerow({
                    "Brand/Model": f"{brand}/{name}" if brand and name else (brand or name),
                    "UPC": prod.get("upc",""),
                    "MPN": prod.get("manufacturerPartNumber",""),
                    "Caliber": prod.get("attributes",{}).get("caliber",""),
                    "Type": prod.get("attributes",{}).get("firearmtype","")
                })

if __name__ == "__main__":
    main()
