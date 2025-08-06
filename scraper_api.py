import requests
import csv
import math

BASE_URL = "https://www.rkguns.com/on/demandware.store/Sites-rkguns-Site/default/Search-UpdateGrid"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Title", "Brand", "Model Name", "UPC", "MPN", "Caliber", "Type"]

def make_session():
    s = requests.Session()
    s.headers.update({
        "User-Agent": "Mozilla/5.0",
        "Accept": "application/json, text/javascript, */*; q=0.01",
        "X-Requested-With": "XMLHttpRequest",
        "Referer": "https://www.rkguns.com/firearms.html?page=1&numResults=36"
    })
    s.cookies.set("hasVerifiedAge", "true", domain="www.rkguns.com")
    return s

def fetch_page(session, offset):
    resp = session.get(BASE_URL, params={
        "cgid": "firearms",
        "start": offset,
        "sz": 36,
        "format": "ajax"
    })
    resp.raise_for_status()
    return resp.json()

def main():
    sess = make_session()
    first = fetch_page(sess, 0)
    total = first["grid"]["hits"]
    pages = math.ceil(total / 36)
    print(f"Total items: {total}, pages: {pages}")

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as fp:
        writer = csv.DictWriter(fp, fieldnames=FIELDS)
        writer.writeheader()

        for i in range(pages):
            offset = i * 36
            print(f"Fetching page {i+1}/{pages} (items {offset+1}–{min(offset+36, total)})", flush=True)
            data = fetch_page(sess, offset)
            for prod in data["grid"]["products"]:
                title = prod.get("pageTitle", "").strip()
                brand = prod.get("brand", "").strip()
                name  = prod.get("productName", "").strip() or prod.get("pageTitle","").strip()
                upc   = prod.get("upc", "")
                mpn   = prod.get("manufacturerPartNumber", "")
                attrs = prod.get("attributes", {})
                caliber = attrs.get("caliber", "")
                ftype   = attrs.get("firearmtype", "")

                writer.writerow({
                    "Title":      title,
                    "Brand":      brand,
                    "Model Name": name,
                    "UPC":        upc,
                    "MPN":        mpn,
                    "Caliber":    caliber,
                    "Type":       ftype.capitalize() if ftype else ""
                })

    print("✅ Done — inventory.csv created")

if __name__ == "__main__":
    main()
