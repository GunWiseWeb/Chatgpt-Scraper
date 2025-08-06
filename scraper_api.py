import csv
import math
from playwright.sync_api import sync_playwright

LIST_URL = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS = ["Title", "Brand", "Model Name", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278

def main():
    with sync_playwright() as pw:
        browser = pw.chromium.launch(headless=True)
        context = browser.new_context()
        # seed age-gate cookie
        context.add_cookies([{
            "name": "hasVerifiedAge",
            "value": "true",
            "domain": "www.rkguns.com",
            "path": "/"
        }])
        page = context.new_page()

        # prepare CSV
        with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as f:
            writer = csv.DictWriter(f, fieldnames=FIELDS)
            writer.writeheader()

            for p in range(1, TOTAL_PAGES + 1):
                print(f"→ Page {p}/{TOTAL_PAGES}")
                # intercept the grid XHR
                grid_json = None
                def handle_route(route):
                    nonlocal grid_json
                    req = route.request
                    if "Search-UpdateGrid" in req.url and req.method == "GET":
                        route.continue_()
                        resp = route.fetch()
                        grid_json = resp.json()
                    else:
                        route.continue_()

                page.route("**/Search-UpdateGrid**", handle_route)
                page.goto(LIST_URL.format(p))
                # wait for at least one card to ensure the XHR fired
                page.wait_for_selector("a.cio-product-card")

                if not grid_json:
                    print("⚠️ Grid JSON not captured on page", p)
                    continue

                products = grid_json["grid"]["products"]
                print(f"   Got {len(products)} items")
                for prod in products:
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

                # reset for next page
                grid_json = None
                page.unroute("**/Search-UpdateGrid**", handle_route)

        browser.close()
    print("✅ Done — inventory.csv created")

if __name__ == "__main__":
    main()
