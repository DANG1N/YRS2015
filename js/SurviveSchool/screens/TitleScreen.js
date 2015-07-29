me.ScreenObject.extend({
    /**
     * action to perform on state change
     */
    onResetEvent : function() {
        me.game.world.addChild(new me.Sprite(0, 0, {
            'image' : me.loader.getImage('title_screen')
        }), 1);

        var message = "FINDING YOUR LOCATION";

        GeoLocation.wait(function(loc) {
            if (loc == null) {
                message = "FAILED TO FIND YOU LOCATION";
                return;
            }
            var lat = loc.coords.latitude;
            var long = loc.coords.longitude;
            message = "GOT LOCATION, SEARCHING FOR SCHOOL";
            ajax.get('/school/find/' + lat + "," + long, function(ret) {
                if (ret == false) {
                    message = "COULD NOT FIND NEARBY SCHOOL";
                } else {
                    var school = ret;
                    message = "YOUR SCHOOL IS: " + school.name.toUpperCase() + "\n\n  PRESS ENTER TO PLAY";
                    me.input.bindKey(me.input.KEY.ENTER, "enter", true);
                    me.input.bindPointer(me.input.mouse.LEFT, me.input.KEY.ENTER);
                }
            });
        });

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
                for (var i = 0; i < message.length % 40; i++) {
                    this.font.draw(renderer, message.substring(i * 40, (i + 1) * 40), 0, 240 + (i * (32 + 5)));
                }
            }
        })), 2);

        this.handler = me.event.subscribe(me.event.KEYDOWN, function(action, keyCode, edge) {
            if (action === "enter") {
                // play something on tap / enter
                // this will unlock audio on mobile devices
                me.audio.play("cling");
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
