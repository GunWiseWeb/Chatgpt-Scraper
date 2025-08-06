import requests
import csv
import math

# Note the shortened path
BASE_URL = "https://www.rkguns.com/Search-UpdateGrid"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Title", "Brand", "Model Name", "UPC", "MPN", "Caliber", "Type"]
PAGE_SIZE = 36

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

def fetch_page(sess, offset):
    resp = sess.get(BASE_URL, params={
        "cgid": "firearms",
        "start": offset,
        "sz": PAGE_SIZE,
        "format": "ajax"
    })
    resp.raise_for_status()
    return resp.json()

def main():
    sess = make_session()
    first = fetch_page(sess, 0)
    total = first["grid"]["hits"]
    pages = math.ceil(total / PAGE_SIZE)
    print(f"Total items: {total} → {pages} pages", flush=True)

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as fp:
        writer = csv.DictWriter(fp, fieldnames=FIELDS)
        writer.writeheader()

        for p in range(pages):
            offset = p * PAGE_SIZE
            print(f"Fetching page {p+1}/{pages} (items {offset+1}–{min(offset+PAGE_SIZE, total)})", flush=True)
            data = fetch_page(sess, offset)
            for prod in data["grid"]["products"]:
                title = prod.get("pageTitle","").strip()
                brand = prod.get("brand","").strip()
                model = prod.get("productName","").strip() or title
                upc   = prod.get("upc","")
                mpn   = prod.get("manufacturerPartNumber","")
                attrs = prod.get("attributes",{})
                caliber = attrs.get("caliber","")
                ftype   = attrs.get("firearmtype","").capitalize()

                writer.writerow({
                    "Title":      title,
                    "Brand":      brand,
                    "Model Name": model,
                    "UPC":        upc,
                    "MPN":        mpn,
                    "Caliber":    caliber,
                    "Type":       ftype
                })

    print("✅ Done — inventory.csv created", flush=True)

if __name__ == "__main__":
    main()
