from mscraper.mscraper import Scraper  # requires magento-py-scraper installed
import csv
import math

class RKScraper(Scraper):
    BASE_URL = 'https://www.rkguns.com/firearms.html'
    AJAX_URL = (
        'https://www.rkguns.com/on/demandware.store/'
        'Sites-rkguns-Site/default/Search-UpdateGrid'
    )
    NUM_RESULTS = 36

    def __init__(self):
        super().__init__()
        # Bypass age gate
        self.session.cookies.set('hasVerifiedAge', 'true', domain='www.rkguns.com')
        self.session.headers.update({
            'User-Agent': 'Mozilla/5.0 (compatible; InventoryScraper/1.0)',
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'X-Requested-With': 'XMLHttpRequest',
            'Referer': f'{self.BASE_URL}?page=1&numResults={self.NUM_RESULTS}'
        })

    def get_total(self):
        data = self.session.get(
            self.AJAX_URL,
            params={'cgid': 'firearms', 'start': 0, 'sz': self.NUM_RESULTS, 'format':'ajax'}
        ).json()
        return data['grid']['hits']

    def parse_page(self, offset):
        params = {'cgid': 'firearms', 'start': offset, 'sz': self.NUM_RESULTS, 'format':'ajax'}
        data = self.session.get(self.AJAX_URL, params=params).json()
        rows = []
        for prod in data['grid']['products']:
            brand = prod.get('brand', '').strip()
            name = (prod.get('pageTitle','') or prod.get('productName','') or prod.get('name','')).strip()
            rows.append({
                'Brand/Model': f"{brand}/{name}" if brand and name else (brand or name),
                'UPC': prod.get('upc', ''),
                'MPN': prod.get('manufacturerPartNumber', ''),
                'Caliber': prod.get('attributes', {}).get('caliber', ''),
                'Type': prod.get('attributes', {}).get('firearmtype', '')
            })
        return rows

def main():
    scraper = RKScraper()
    total = scraper.get_total()
    pages = math.ceil(total / scraper.NUM_RESULTS)

    with open('inventory.csv', 'w', newline='', encoding='utf-8') as fh:
        writer = csv.DictWriter(fh, fieldnames=['Brand/Model','UPC','MPN','Caliber','Type'])
        writer.writeheader()
        for i in range(pages):
            offset = i * scraper.NUM_RESULTS
            print(f"Scraping offset {offset}…")
            for row in scraper.parse_page(offset):
                writer.writerow(row)

if __name__ == '__main__':
    main()
