<?php
if (in_array('parallel', stream_get_transports())) {
    echo "✅ El transporte 'parallel' está disponible.";
} else {
    echo "❌ El transporte 'parallel' NO está disponible.";
}
?>