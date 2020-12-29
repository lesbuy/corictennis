// a phantomjs example

var page = require('webpage').create();
phantom.outputEncoding="utf-8";

/*
phantom.addCookie({
	"domain": ".itftennis.com",
	"expires": "Sun, 12 Sep 2021 21:19:16 GMT",
	"httponly": true,
	"name": "visid_incap_178373",
	"path": "/",
	"secure": false,
	"value": "Gy+hzUaUQjWpjEqnl5GSsvcVXl8AAAAAQUIPAAAAAADqQJdavISZ6oUiJ88Mki8o" 
});
phantom.addCookie({
	"domain": ".itftennis.com",
	"expires": "Mon, 13 Sep 2021 13:09:21 GMT",
	"httponly": false,
	"name": "incap_ses_895_178373",
	"path": "/",
	"secure": false,
	"value": "e1MKHAp6Ek8RqoCx261rDPcVXl8AAAAAPzfPWUzoAY15LPq7SqAKKg==" 
});
phantom.addCookie({
	"domain": ".www.itftennis.com",
	"expires": "Mon, 13 Sep 2021 13:10:23 GMT",
	"httponly": true,
	"name": "ARRAffinity",
	"path": "/",
	"secure": false,
	"value": "a28963739ebb80d79fbbac2c2b5113c8b66dec67239e314ceb179cc6abb222b9" 
});
phantom.addCookie({
	"domain": ".itftennis.com",
	"expires": "Sat, 12 Dec 2020 13:07:57 GMT",
	"httponly": false,
	"name": "_fbp",
	"path": "/",
	"secure": false,
	"value": "fb.1.1600001529092.665214098" 
});
phantom.addCookie({
	"domain": ".itftennis.com",
	"expires": "Mon, 14 Sep 2020 12:52:38 GMT",
	"httponly": false,
	"name": "_gid",
	"path": "/",
	"secure": false,
	"value": "GA1.2.399903359.1600001529" 
});
phantom.addCookie({
	"domain": ".itftennis.com",
	"expires": "Tue, 13 Sep 2022 13:07:56 GMT",
	"httponly": false,
	"name": "_ga",
	"path": "/",
	"secure": false,
	"value": "GA1.2.1864101186.1600001529" 
});
*/

page.open("https://www.atptour.com/en/rankings/singles?rankDate=2020-09-21&rankRange=0-100", function(status) {
   if ( status === "success" ) {
      console.log(page.title); 
   } else {
      console.log("Page failed to load." + "   " + status); 
   }
   phantom.exit(0);
});
