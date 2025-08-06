import os, csv, time, chromedriver_autoinstaller
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

# auto-install the right chromedriver
chromedriver_autoinstaller.install()

START_PAGE = int(os.getenv("START_PAGE", "1"))
END_PAGE   = int(os.getenv("END_PAGE",   "278"))
LIST_URL   = "https://www.rkguns.com/firearms.html?page={}&numResults=36"
OUT_FILE   = f"inventory-{START_PAGE}-{END_PAGE}.csv"

FIELDS = ["Brand/Model", "UPC", "MPN", "Caliber", "Type"]

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

        for page in range(START_PAGE, END_PAGE + 1):
            print(f"→ Page {page}/{END_PAGE}", flush=True)
            driver.get(LIST_URL.format(page))
            wait.until(EC.presence_of_element_located((By.CSS_SELECTOR, "a.cio-product-card")))

            cards = driver.find_elements(By.CSS_SELECTOR, "a.cio-product-card")
            for card in cards:
                name = card.get_attribute("data-cnstrc-item-name") or ""
                upc  = card.get_attribute("data-cnstrc-item-upc")   or ""
                mpn  = card.get_attribute("data-cnstrc-item-mpn")   or ""
                # no caliber/type attributes on card, leave blank
                writer.writerow({
                    "Brand/Model": name,
                    "UPC":         upc,
                    "MPN":         mpn,
                    "Caliber":     "",
                    "Type":        ""
                })
            time.sleep(0.2)

    driver.quit()
    print(f"✅ Done chunk {START_PAGE}-{END_PAGE}")

if __name__ == "__main__":
    main()
