import time 
import sys 
import urllib3 
import json 

http = urllib3.PoolManager()    
BRANCH_NAME = 'dev-{}'.format(sys.argv[1])
GIT_BRANCH_REF = sys.argv[2]
REPO_API_URL = 'https://packagist.org/packages/PargoPoints/plugin-magento.json'

def get_package_version():
  print ("Git Ref: {}".format(GIT_BRANCH_REF))
  #version_data = requests.get(REPO_API_URL)
  headers = {'Cache-Control': 'no-cache', 'Pragma': 'no-cache', 'Expires': 'Thu, 01 Jan 1970 00:00:00 GMT'}
  source_ref = 'a'
  dist_ref = 'b'
  while dist_ref != GIT_BRANCH_REF and source_ref != GIT_BRANCH_REF:
    try:
        version_data = http.request('GET', REPO_API_URL, headers=headers)
        version_json = (json.loads(version_data.data))
        print (json.dumps(version_json,indent=4))
        print ("Packagist Source Ref: {}, Packagist Dist Ref: {}".format(source_ref,dist_ref))
        source_ref = (version_json['package']['versions'][BRANCH_NAME]['source']['reference'])
        dist_ref = (version_json['package']['versions'][BRANCH_NAME]['dist']['reference'])
        print ("Checking branch refs...")
    except Exception as e:
        print ("Unable to find packagist info for {}".format(e))
    time.sleep(5)

  print ("GIT_REF matches packagist refs")

get_package_version()