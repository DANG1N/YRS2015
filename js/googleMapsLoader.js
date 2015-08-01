var GoogleMapsLoader = new function() {
    function loadApi(onLoad) {
        window.__loaderTemp = loadApi; // TODO when I'm not asleep
        if (!window.google || !window.google.maps) {
            window.google = window.google || {}; // Ensure don't load twice
            window.google.maps = window.google.maps || {};
            var script = document.createElement('script');
            document.write = function(html) { // TODO Find a proper solution
                var i1 = html.indexOf('"');
                var src = html.substring(i1 + 1, html.indexOf('"', i1 + 1));
                var script = document.createElement('script');
                script.src = src;
                document.head.appendChild(script);
            };
            document.head.appendChild(script);
            script.src = 'https://maps.googleapis.com/maps/api/js?v=3.exp';
            loader = this;
            setTimeout(function() {
                __loaderTemp(onLoad);
            }, 100);
        } else {
            if (!window.google.maps.Map) {
                setTimeout(function() {
                    __loaderTemp(onLoad);
                }, 100);
            } else {
                onLoad();
                delete window.__loaderTemp;
            }
        }
    }

    this.overlayGame = function() {
        loadApi(function() {
            document.getElementById('screen').style.display = 'none';
            var coords = GeoLocation.getLocation().coords;
            var pos = {
                lat : coords.latitude,
                lng : coords.longitude
            };
            map = new google.maps.Map(document.getElementById('map-canvas'), {
                zoom : 16,
                center : pos
            });
            var marker = new google.maps.Marker({
                position : pos,
                map : map,
                title : 'You are here'
            });
        });
    };
};
