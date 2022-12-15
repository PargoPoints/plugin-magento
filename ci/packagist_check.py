import time 
import sys 
import urllib3 
import json 

http = urllib3.PoolManager()    
BRANCH_NAME = 'dev-{}'.format(sys.argv[1])
GIT_BRANCH_REF = sys.argv[2]
REPO_API_URL = 'https://packagist.org/packages/PargoPoints/plugin-magento.json'

def get_package_version():
  REPO_API_URL = 'https://repo.packagist.org/p2/pargopoints/plugin-magento~dev.json'
  headers = {'Cache-Control': 'no-cache', 'Pragma': 'no-cache', 'Expires': 'Thu, 01 Jan 1970 00:00:00 GMT'}
  source_ref = 'a'
  dist_ref = 'b'
  while dist_ref != GIT_BRANCH_REF and source_ref != GIT_BRANCH_REF:
    version_data = http.request('GET', REPO_API_URL, headers=headers)
    version_json = (json.loads(version_data.data))
    print (json.dumps(version_json, indent=4))
    for item in version_json['packages']['pargopoints/plugin-magento']:
      if item['version'] == BRANCH_NAME:
        source_ref = (item['source']['reference'])
        dist_ref = (item['dist']['reference'])
    print (source_ref)
    print (dist_ref)

get_package_version()