import requests
import csv
import math

BASE_URL = (
    "https://www.rkguns.com/on/demandware.store/"
    "Sites-rkguns-Site/default/Search-UpdateGrid"
)
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Title", "Brand", "Model Name", "UPC", "MPN", "Caliber", "Type"]
PAGE_SIZE = 36

def make_session():
    s = requests.Session()
    s.headers.update({
        "User-Agent": "Mozilla/5.0",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "Referer": "https://www.rkguns.com/firearms.html?page=1&numResults=36"
    })
    s.cookies.set("hasVerifiedAge", "true", domain="www.rkguns.com")
    return s

def fetch_page(session, offset):
    r = session.get(BASE_URL, params={
        "cgid": "firearms",
        "start": offset,
        "sz": PAGE_SIZE
    })
    r.raise_for_status()
    return r.json()

def main():
    sess = make_session()
    first = fetch_page(sess, 0)
    total = first["grid"]["hits"]
    pages = math.ceil(total / PAGE_SIZE)
    print(f"Total items: {total}, pages: {pages}")

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for p in range(pages):
            offset = p * PAGE_SIZE
            print(f"Fetching page {p+1}/{pages}", flush=True)
            data = fetch_page(sess, offset)
            for prod in data["grid"]["products"]:
                title = prod.get("pageTitle", "").strip()
                brand = prod.get("brand", "").strip()
                model = prod.get("productName", "").strip()
                upc   = prod.get("upc", "")
                mpn   = prod.get("manufacturerPartNumber", "")
                attrs = prod.get("attributes", {})
                caliber = attrs.get("caliber", "")
                ftype   = attrs.get("firearmtype", "").capitalize()
                writer.writerow({
                    "Title":      title,
                    "Brand":      brand,
                    "Model Name": model,
                    "UPC":        upc,
                    "MPN":        mpn,
                    "Caliber":    caliber,
                    "Type":       ftype
                })

    print("✅ Done — inventory.csv written")
