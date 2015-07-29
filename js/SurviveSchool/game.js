var surviveSchool = GameRegistry.gameBuilder('SurviveSchool').maps([ 'School' ]).entities([ 'PlayerEntity' ]).screens([
        'TitleScreen', 'PlayScreen' ]).sprites([ 'rpg_sprite_walk' ]).build();

// TODO temporary
me.loader.preload([ {
    "name" : "title_screen",
    "type" : "image",
    "src" : "assets/SurviveSchool/gui/title_screen.png"
} ]);

surviveSchool.onLoad = function() {
    me.pool.register("mainPlayer", this.entityRegistry['PlayerEntity']);

    me.state.set(me.state.MENU, new this.screenRegistry.TitleScreen());
    me.state.set(me.state.PLAY, new this.screenRegistry.PlayScreen());

    me.input.bindKey(me.input.KEY.LEFT, "left");
    me.input.bindKey(me.input.KEY.RIGHT, "right");
    me.input.bindKey(me.input.KEY.UP, "up");
    me.input.bindKey(me.input.KEY.DOWN, "down");

    me.state.change(me.state.MENU);
};
