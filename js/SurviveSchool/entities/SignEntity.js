me.Entity.extend({

    init : function(x, y, settings) {
        this._super(me.Entity, 'init', [ x, y, settings ]);
        this.font = new me.Font("Verdana", 14, "white");
    },

    draw : function(context) {
        this._super(me.Entity, 'draw', [ context ]);
        this.font.draw(context, surviveSchool.data.schoolName, this.pos.x, this.pos.y);
    }
});
