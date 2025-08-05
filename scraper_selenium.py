# File: scraper_selenium.py

import csv
import time
import chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from bs4 import BeautifulSoup

chromedriver_autoinstaller.install()

LIST_URL    = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUTPUT_FILE = "inventory.csv"
FIELDS      = ["Title", "Brand", "Model Name", "UPC", "MPN", "Caliber", "Type"]
TOTAL_PAGES = 278

def parse_detail_page(html: str):
    soup = BeautifulSoup(html, "html.parser")
    title_el = soup.select_one("h1.page-title")
    title    = title_el.get_text(strip=True) if title_el else ""

    specs = {}
    for row in soup.select("table.product-specs tr"):
        th = row.select_one("th")
        td = row.select_one("td")
        if th and td:
            specs[th.get_text(strip=True)] = td.get_text(strip=True)

    desc_el = soup.select_one(".product-description") or soup.select_one("#description")
    desc = desc_el.get_text(" ", strip=True).lower() if desc_el else ""
    for kind in ("pistol", "revolver", "rifle", "shotgun"):
        if kind in desc:
            ptype = kind.capitalize()
            break
    else:
        ptype = "Other"

    return {
        "Title":      title,
        "Brand":      specs.get("Brand", ""),
        "Model Name": specs.get("Model Name", specs.get("Model", "")),
        "UPC":        specs.get("UPC", ""),
        "MPN":        specs.get("MPN", specs.get("Manufacturer Part Number", "")),
        "Caliber":    specs.get("Caliber", ""),
        "Type":       ptype
    }

def main():
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait   = WebDriverWait(driver, 10)

    # Seed age-gate cookie
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name":"hasVerifiedAge","value":"true","domain":"www.rkguns.com"})

    with open(OUTPUT_FILE, "w", newline="", encoding="utf-8") as fh:
        writer = csv.DictWriter(fh, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(1, TOTAL_PAGES + 1):
            print(f"→ Listing page {page}/{TOTAL_PAGES}", flush=True)
            driver.get(LIST_URL.format(page))
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "a.cio-product-card")))

            hrefs = [e.get_attribute("href")
                     for e in driver.find_elements(By.CSS_SELECTOR, "a.cio-product-card")]
            print(f"   Collected {len(hrefs)} detail URLs", flush=True)

            for href in hrefs:
                print(f"     Fetching detail: {href}", flush=True)
                driver.get(href)
                time.sleep(2)  # brief pause for HTML to load

                data = parse_detail_page(driver.page_source)
                writer.writerow(data)

    driver.quit()
    print("✅ Done — inventory.csv created", flush=True)

if __name__ == "__main__":
    main()
