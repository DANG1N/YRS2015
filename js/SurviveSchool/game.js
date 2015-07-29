var surviveSchool = GameRegistry.gameBuilder('SurviveSchool').maps([ 'School' ]).entities([ 'PlayerEntity' ])
        .screens([ 'PlayScreen' ]).sprites([ 'rpg_sprite_walk' ]).build();

surviveSchool.onLoad = function() {
    // this.pool.register("Player", this.entityRegistry.get('PlayerEntity'));
    me.pool.register("mainPlayer", this.entityRegistry['PlayerEntity']);

    me.state.set(me.state.PLAY, new this.screenRegistry.PlayScreen());

    me.input.bindKey(me.input.KEY.LEFT, "left");
    me.input.bindKey(me.input.KEY.RIGHT, "right");
    me.input.bindKey(me.input.KEY.UP, "up");
    me.input.bindKey(me.input.KEY.DOWN, "down");

    me.state.change(me.state.PLAY);
};

/**
 * a HUD container and child items
 */

surviveSchool.HUD = {};

surviveSchool.HUD.Container = me.Container.extend({

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
        this.addChild(new surviveSchool.HUD.ScoreItem(5, 5));
    }
});

/**
 * a basic HUD item to display score
 */
surviveSchool.HUD.ScoreItem = me.Renderable.extend({
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
        // if (this.score !== game.data.score) {
        // this.score = game.data.score;
        // return true;
        // }
        return false;
    },

    /**
     * draw the score
     */
    draw : function(context) {
        // draw it baby !
    }

});
