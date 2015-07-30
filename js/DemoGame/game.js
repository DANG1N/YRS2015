var demoGame = GameRegistry.gameBuilder("DemoGame").maps([ 'area01' ]).sprites([ 'gripe_run_right' ])
        .screens([ 'PlayScreen' ]).entities([ 'PlayerEntity', 'CoinEntity', 'EnemyEntity' ]).build();

demoGame.onLoad = function() {
    console.log("Demo Load")
    // set the "Play/Ingame" Screen Object
    me.state.set(me.state.USER + 101, new this.screenRegistry.PlayScreen());

    // add our player entity in the entity pool
    me.pool.register("demoPlayer", this.entityRegistry.PlayerEntity);
    me.pool.register("CoinEntity", this.entityRegistry.CoinEntity);
    me.pool.register("EnemyEntity", this.entityRegistry.EnemyEntity);

    // enable the keyboard
    me.input.bindKey(me.input.KEY.LEFT, "left");
    me.input.bindKey(me.input.KEY.RIGHT, "right");
    me.input.bindKey(me.input.KEY.X, "jump", true);

    // Start the game.
    me.state.change(me.state.USER + 101);
};

demoGame.data = {
    score : 0
};

/**
 * a HUD container and child items
 */

demoGame.HUD = {};

demoGame.HUD.Container = me.Container.extend({

    init : function() {
        // call the constructor
        this._super(me.Container, 'init');

        // persistent across level change
        this.isPersistent = true;

        // make sure we use screen coordinates
        this.floating = true;

        // make sure our object is always draw first
        this.z = Infinity;

        // give a name
        this.name = "HUD";

        // add our child score object at the top left corner
        this.addChild(new demoGame.HUD.ScoreItem(5, 5));
    }
});

/**
 * a basic HUD item to display score
 */
demoGame.HUD.ScoreItem = me.Renderable.extend({
    /**
     * constructor
     */
    init : function(x, y) {

        // call the parent constructor
        // (size does not matter here)
        this._super(me.Renderable, 'init', [ x, y, 10, 10 ]);

        // local copy of the global score
        this.score = -1;
    },

    /**
     * update function
     */
    update : function() {
        // we don't do anything fancy here, so just
        // return true if the score has been updated
        if (this.score !== demoGame.data.score) {
            this.score = demoGame.data.score;
            return true;
        }
        return false;
    },

    /**
     * draw the score
     */
    draw : function(context) {
        // draw it baby !
    }

});
