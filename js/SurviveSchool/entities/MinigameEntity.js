me.Entity.extend({

    init : function(x, y, settings) {
        this._super(me.Entity, 'init', [ x, y, settings ]);
        this.minigame = settings.minigame;
    }
});
