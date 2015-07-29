/* Game namespace */
var game = {

    // Run on page load.
    "onload" : function() {
        // Initialize the video.
        if (!me.video.init(960, 640, {
            wrapper : "screen",
            scale : "auto"
        })) {
            alert("Your browser does not support HTML5 canvas.");
            return;
        }

        // add "#debug" to the URL to enable the debug Panel
        if (me.game.HASH.debug === true) {
            window.onReady(function() {
                me.plugin.register.defer(this, me.debug.Panel, "debug", me.input.KEY.V);
            });
        }

        // Initialize the audio.
        me.audio.init("mp3,ogg");

        // Set a callback to run when loading is complete.
        me.loader.onload = this.loaded.bind(this);

        me.loader.preload([ {
            "name" : "32x32_font",
            "type" : "image",
            "src" : "assets/_shared/fonts/main_32x32.png"
        } ]);

        GameRegistry.initializeGame('SurviveSchool');

        // Initialize melonJS and display a loading screen.
        me.state.change(me.state.LOADING);
    },

    // Run on game resources loaded.
    "loaded" : function() {
        var mainGame = GameRegistry.getGame('SurviveSchool');
        if (!mainGame) {
            setTimeout(this, 0.1);
            return
        }
        mainGame.loadWhenReady();
    }
};
