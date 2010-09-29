OpenLayers.ImgPath = "http://openlayers.org/api/img/";

var map;
var layer;
var width;
var height;
var angle;

function init()
{
    
    map = new OpenLayers.Map("olMap");
    
    width = <?php echo $olGraphicWidth; ?>;
    height = <?php echo $olGraphicHeight; ?>;
    angle = 0;
    
    var styleTemplate = {
        externalGraphic: "<?php echo $olExternalGraphicUrl; ?>",
        graphicWidth: "${getWidth}",
        graphicHeight: "${getHeight}",
        rotation: "${getAngle}"
    };
    
    var styleContext = {
        getWidth: function() {
            return width / map.getResolution();
        }, 
        getHeight: function() {
            return height / map.getResolution();
        }, 
        getAngle: function() {
            return angle;
        }
    };
    
    layer = new OpenLayers.Layer.Vector(
        "Document Image Layer",
        {
            isBaseLayer: true, 
            styleMap: new OpenLayers.Style(styleTemplate, {context: styleContext})
        }
    );
    
    map.addLayer(layer);
    map.zoomToMaxExtent();
    
    var feature = new OpenLayers.Feature.Vector(
        new OpenLayers.Geometry.Point(0, 0)
    )
    
    // OpenLayers library hack
    // See: http://gis.ibbeck.de/ginfo/apps/OLExamples/OL27/examples/ExternalGraphicOverlay/OpenLayers2.7full_renderer.js
    feature.attributes.render = "drawAlways";
    
    layer.addFeatures([feature]);
    
    var dragControl = new OpenLayers.Control.DragFeature(layer);
    map.addControl(dragControl);
    dragControl.activate();
};

var rotationIntervalId;

function rotate(degrees, interval)
{
    rotationIntervalId = setInterval('rotateGraphic(' + degrees + ')', interval);
}

function stopRotate()
{
    clearInterval(rotationIntervalId);
}

function rotateGraphic(degrees)
{
    if (degrees == 0) {
        angle = 0;
    } else {
        angle = angle + degrees;
    }
    layer.drawFeature(layer.features[0]);
}