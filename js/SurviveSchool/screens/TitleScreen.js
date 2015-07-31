me.ScreenObject.extend({
    /**
     * action to perform on state change
     */
    onResetEvent : function() {
        me.game.world.addChild(new me.Sprite(0, 0, {
            'image' : me.loader.getImage('title_screen')
        }), 1);

        var message = "FINDING YOUR LOCATION";

        function enablePlay(schoolName) {
            message = "YOUR SCHOOL IS: " + schoolName.toUpperCase() + "\n\nPRESS ENTER TO PLAY";
            surviveSchool.data.schoolName = schoolName;
            me.input.bindKey(me.input.KEY.ENTER, "enter", true);
            me.input.bindPointer(me.input.mouse.LEFT, me.input.KEY.ENTER);
        }

        function querySchoolName() {
            var name = prompt("Enter school name", "Unnamed School");
            if (name) {
                setTimeout(function() {
                    enablePlay(name);
                }, 50);
            } else {
                querySchoolName();
            }
        }

        GeoLocation.wait(function(loc) {
            if (loc == null) {
                message = "FAILED TO FIND YOUR LOCATION";
                setTimeout(querySchoolName, 50);
                return;
            }
            var lat = loc.coords.latitude;
            var long = loc.coords.longitude;
            message = "GOT LOCATION, SEARCHING FOR SCHOOL";
            ajax.get('/school/find/' + lat + "," + long, function(school) {
                if (school == false) {
                    message = "COULD NOT FIND NEARBY SCHOOL";
                    setTimeout(querySchoolName, 50);
                } else {
                    enablePlay(school.name);
                }
            });
        });

        var maxCharLen = 35;

        me.game.world.addChild(new (me.Renderable.extend({
            // constructor
            init : function() {
                this._super(me.Renderable, 'init', [ 0, 0, me.game.viewport.width, me.game.viewport.height ]);
                // font for the scrolling text
                this.font = new me.BitmapFont("32x32_font", 32);
                this.font.resize(0.75);
            },

            update : function(dt) {
                return true;
            },

            draw : function(renderer) {
                var lines = message.split('\n');
                var shift = 0;
                for (var i = 0; i < lines.length; i++) {
                    shift += this.drawLine(renderer, lines[i], 80, 320 + shift);
                }
            },

            drawLine : function(renderer, line, x, y) {
                var len = (line.length / maxCharLen) + 1;
                for (var i = 0; i < len; i++) {
                    this.font.draw(renderer, line.substring(i * maxCharLen, (i + 1) * maxCharLen), x, y
                            + (i * (32 + 5)));
                }
                return len * 37;
            }
        })), 2);

        this.handler = me.event.subscribe(me.event.KEYDOWN, function(action, keyCode, edge) {
            if (action === "enter") {
                me.input.unbindKey(me.input.KEY.ENTER);
                me.input.unbindPointer(me.input.mouse.LEFT);
                me.state.change(me.state.PLAY);
            }
        });
    },

    /**
     * action to perform when leaving this screen (state change)
     */
    onDestroyEvent : function() {
        ; // TODO
    }
});
