me.Entity.extend({

    init : function(x, y, settings) {
        this._super(me.Entity, 'init', [ x, y, settings ]);
        this.font = new me.Font("Verdana", 14, "white");
    },

    draw : function(context) {
        this._super(me.Entity, 'draw', [ context ]);
        var name = surviveSchool.data.schoolName;
        var maxLen = name.length;
        do {
            var size = this.font.measureText(context, name.substring(0, maxLen--));
        } while (size.width > this.width);
        maxLen++;

        for (var i = 0; i < (name.length / maxLen) + 1; i++) {
            this.font.draw(context, name.substring(i * maxLen, maxLen * (i + 1)), this.pos.x, this.pos.y
                    + (size.height * i));
        }
    }
});
