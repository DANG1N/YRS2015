var ScratchLoader = new function() {
    var overlay = document.getElementById('scratch');
    var challengeDiv = document.getElementById('scratchChallenge');
    var iFrame = null;
    var countdown = document.getElementById('countdown');
    var intervalId = 0;

    this.overlayProject = function(projectId, onFinish) {
        console.log("Loading scratch project", projectId);
        if (iFrame && parseInt(iFrame.getAttribute('data-projectId')) == projectId) {
            return;
        }
        ScratchLoader.clearOverlay();
        iFrame = document.createElement('iframe');
        // iFrame.style.marginTop = '-38px';
        iFrame.setAttribute('allowtransparency', true);
        iFrame.width = Math.min(me.video.renderer.gameWidthZoom, 900);
        iFrame.height = Math.min(me.video.renderer.gameHeightZoom, 800);
        overlay.style.display = 'block';
        overlay.style.width = me.video.renderer.gameWidthZoom + 'px';
        overlay.style.height = me.video.renderer.gameHeightZoom + 'px';
        iFrame.src = "//scratch.mit.edu/projects/embed/" + projectId + "/?autostart=true";
        iFrame.setAttribute('frameborder', 0);
        iFrame.setAttribute('allowfullscreen', true);
        iFrame.setAttribute('data-projectId', projectId);
        overlay.appendChild(iFrame);
        document.getElementById('screen').style.display = 'none';
        challengeDiv.style.display = 'initial';
        var remaining = 120;
        intervalId = setInterval(function() {
            countdown.textContent = remaining;
            remaining -= 0.5;
            if (remaining <= 0) {
                clearInterval(intervalId);
                countdown.textContent = "Time is up!";
            }
        }, 500);

        document.getElementById('returnClick').onclick = function(event) {
            event.preventDefault();
            console.log("Exit");
            ScratchLoader.clearOverlay();
            onFinish && onFinish();
        };
    };

    this.clearOverlay = function() {
        if (iFrame) {
            overlay.removeChild(iFrame);
            iFrame = null;
            challengeDiv.style.display = 'none';
            document.getElementById('screen').style.display = 'initial';
            clearInterval(intervalId);
            overlay.style.display = 'none';
        }
    };
};
