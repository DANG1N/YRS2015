me.ScreenObject.extend({
    /**
     * action to perform on state change
     */
    onResetEvent : function() {
        surviveSchool.levelDirector.loadLevel("School");
    },

    /**
     * action to perform when leaving this screen (state change)
     */
    onDestroyEvent : function() {
    }
});
