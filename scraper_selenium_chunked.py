# File: scraper_selenium_chunked.py

import os, csv, time, chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from bs4 import BeautifulSoup

# auto-install the right ChromeDriver
chromedriver_autoinstaller.install()

START_PAGE = int(os.getenv("START_PAGE", "1"))
END_PAGE   = int(os.getenv("END_PAGE",   "278"))
LIST_URL   = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUT_FILE   = f"inventory_{START_PAGE}_{END_PAGE}.csv"
FIELDS     = ["Title","Brand","Model Name","UPC","MPN","Caliber","Type"]

def infer_type(text):
    t = text.lower()
    for k in ("pistol","revolver","rifle","shotgun"):
        if k in t:
            return k.capitalize()
    return "Other"

def parse_detail(html):
    soup = BeautifulSoup(html, "html.parser")
    title = (soup.select_one("h1.page-title") or "").get_text(strip=True)
    specs = {}
    for tr in soup.select("table.product-specs tr"):
        th,td = tr.select_one("th"), tr.select_one("td")
        if th and td:
            specs[th.get_text(strip=True)] = td.get_text(strip=True)
    desc = (soup.select_one(".product-description") or "").get_text(" ", strip=True)
    return {
        "Title":      title,
        "Brand":      specs.get("Brand",""),
        "Model Name": specs.get("Model Name", specs.get("Model","")),
        "UPC":        specs.get("UPC",""),
        "MPN":        specs.get("MPN", specs.get("Manufacturer Part Number","")),
        "Caliber":    specs.get("Caliber",""),
        "Type":       infer_type(desc)
    }

def main():
    opts = Options()
    opts.add_argument("--headless")
    opts.add_argument("--no-sandbox")
    opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=opts)
    wait   = WebDriverWait(driver, 15)

    # seed age-gate
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name":"hasVerifiedAge","value":"true","domain":"www.rkguns.com"})

    with open(OUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(START_PAGE, END_PAGE+1):
            print(f"→ Listing page {page}/{END_PAGE}", flush=True)
            driver.get(LIST_URL.format(page))
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "a.cio-product-card")))

            hrefs = [a.get_attribute("href")
                     for a in driver.find_elements(By.CSS_SELECTOR, "a.cio-product-card")]

            print(f"   Found {len(hrefs)} products", flush=True)
            for url in hrefs:
                print(f"     Detail → {url}", flush=True)
                driver.get(url)
                # small pause to let content render
                time.sleep(1)

                data = parse_detail(driver.page_source)
                writer.writerow(data)

    driver.quit()
    print(f"✅ Done chunk {START_PAGE}-{END_PAGE}", flush=True)

if __name__ == "__main__":
    main()
