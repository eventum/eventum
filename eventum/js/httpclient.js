function HTTPClient() {};

HTTPClient.prototype = {
    xmlhttp: null,
    callback: null,
    
    loadRemoteContent: function(url, callbackFunction) 
    {
        this.callback = function(self) {
            eval(callbackFunction + '(self.xmlhttp);');
        }
        
        var self = this;
        
        // branch for native XMLHttpRequest object
        if (window.XMLHttpRequest) {
            this.xmlhttp = new XMLHttpRequest();
            this.xmlhttp.onreadystatechange = function() {
                self.processReqChange(self);
            }
            this.xmlhttp.open("GET", url, true);
            this.xmlhttp.send(null);
        // branch for IE/Windows ActiveX version
        } else if (window.ActiveXObject) {
            this.xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            if (this.xmlhttp) {
                this.xmlhttp.onreadystatechange = function() {
                    self.processReqChange(self);
                };
                this.xmlhttp.open("GET", url, true);
                this.xmlhttp.send();
            }
        }
    },

    processReqChange: function(self) 
    {
        // only if req shows "complete"
        if (this.xmlhttp.readyState == 4) {
            // only if "OK"
            if (this.xmlhttp.status == 200) {
                // ...processing statements go here...
                self.callback(self);
            } else {
                alert("There was a problem retrieving the data:\n" + this.xmlhttp.statusText);
            }
        }
    }
}
