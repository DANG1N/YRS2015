var ScratchLoader = new function() {
    var overlay = document.getElementById('scratch');
    var challengeDiv = document.getElementById('scratchChallenge');
    var iFrame = null;

    this.overlayProject = function(projectId, onFinish) {
        if (iFrame && parseInt(iFrame.getAttribute('data-projectId')) == projectId) {
            return;
        }
        ScratchLoader.clearOverlay();
        iFrame = document.createElement('iframe');
        iFrame.style.marginTop = '-38px';
        iFrame.setAttribute('allowtransparency', true);
        iFrame.width = me.video.renderer.gameWidthZoom;
        iFrame.height = me.video.renderer.gameHeightZoom;
        overlay.style.width = me.video.renderer.gameWidthZoom + 'px';
        overlay.style.height = me.video.renderer.gameHeightZoom + 'px';
        iFrame.src = "http://scratch.mit.edu/projects/embed/" + projectId + "/?autostart=true";
        iFrame.setAttribute('frameborder', 0);
        iFrame.setAttribute('allowfullscreen', true);
        iFrame.setAttribute('data-projectId', projectId);
        overlay.appendChild(iFrame);
        document.getElementById('screen').style.display = 'none';
        window.temp = iFrame;
        challengeDiv.style.display = 'initial';
        setTimeout(function() {
            ScratchLoader.clearOverlay();
            onFinish && onFinish();
        }, 2 * 60 * 1000);
    };

    this.clearOverlay = function() {
        if (iFrame) {
            overlay.removeChild(iFrame);
            iFrame = null;
            challengeDiv.display = 'none';
            document.getElementById('screen').style.display = 'initial';
        }
    };
};
