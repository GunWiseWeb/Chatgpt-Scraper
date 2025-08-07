import os, csv, time, chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

chromedriver_autoinstaller.install()

# Read page bounds from env (set by workflow)
START_PAGE = int(os.getenv("START_PAGE", "1"))
END_PAGE   = int(os.getenv("END_PAGE",   "1"))
LIST_URL   = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUT_FILE   = f"inventory_{START_PAGE}_{END_PAGE}.csv"
FIELDS     = ["Title","Brand","Model Name","UPC","MPN","Caliber"]

def parse_detail(driver):
    # Title
    try:
        title = driver.find_element(By.CSS_SELECTOR, "h1.page-title").text.strip()
    except:
        title = ""

    # Specs via data-th
    specs = {}
    for td in driver.find_elements(By.CSS_SELECTOR, "td.product-attribute-value"):
        key = td.get_attribute("data-th") or ""
        val = td.text or ""
        specs[key.strip()] = val.strip()

    return {
        "Title":      title,
        "Brand":      specs.get("Brand",""),
        "Model Name": specs.get("Model Name", specs.get("Model","")),
        "UPC":        specs.get("UPC",""),
        "MPN":        specs.get("MPN",""),
        "Caliber":    specs.get("Caliber","")
    }

def main():
    chrome_opts = Options()
    chrome_opts.add_argument("--headless")
    chrome_opts.add_argument("--no-sandbox")
    chrome_opts.add_argument("--disable-dev-shm-usage")

    driver = webdriver.Chrome(options=chrome_opts)
    wait   = WebDriverWait(driver, 10)

    # seed age-gate
    driver.get("https://www.rkguns.com/")
    driver.add_cookie({"name":"hasVerifiedAge","value":"true","domain":"www.rkguns.com"})

    with open(OUT_FILE, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=FIELDS)
        writer.writeheader()

        for page in range(START_PAGE, END_PAGE + 1):
            print(f"→ Listing page {page}/{END_PAGE}", flush=True)
            driver.get(LIST_URL.format(page))
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "a.cio-product-card")))

            hrefs = [a.get_attribute("href") 
                     for a in driver.find_elements(By.CSS_SELECTOR, "a.cio-product-card")]
            print(f"   Found {len(hrefs)} products", flush=True)

            for url in hrefs:
                print(f"     Detail → {url}", flush=True)
                driver.get(url)
                time.sleep(0.5)  # reduced pause

                data = parse_detail(driver)
                writer.writerow(data)

    driver.quit()
    print(f"✅ Chunk {START_PAGE}-{END_PAGE} complete", flush=True)

if __name__ == "__main__":
    main()
