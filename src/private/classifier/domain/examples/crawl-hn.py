import requests
from HTMLParser import HTMLParser

# create a subclass and override the handler methods
class MyHTMLParser(HTMLParser):
    def handle_starttag(self, tag, attrs):
    	if(tag=="span" and ('class','sitestr') in attrs):
        	self.now = True
        else:
        	self.now = False

    def handle_endtag(self, tag):
        self.now = False

    def handle_data(self, data):
    	if(self.now):
        	f=open('examples-hn.txt','a')
        	f.write(data+'\r')
        	self.now=False



def fetch(url):
	s = requests.session()
	r = s.get(url)
	t = r.text
	# instantiate the parser and fed it some HTML
	parser = MyHTMLParser()
	
	# instantiate the parser and fed it some HTML
	parser.feed(t)

# 

from datetime import timedelta, date

def daterange(start_date, end_date):
    for n in range(int ((end_date - start_date).days)):
        yield start_date + timedelta(n)

start_date = date(2015, 11, 11)
end_date = date(2016, 11, 12)
for single_date in daterange(start_date, end_date):
    d= single_date.strftime("%Y-%m-%d")
    print d
    fetch('https://news.ycombinator.com/front?day='+d)
    # fetch('https://news.ycombinator.com/front?day=2014-11-11')
