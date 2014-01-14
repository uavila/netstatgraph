<?php
?>
<html>
    <head><title>NetstatGraph</title></head>
    <body>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>
    <script src="/netstatgraph/springy/springy.js"></script>
    <script src="/netstatgraph/springy/springyui.js"></script>
    <script>
    var graph = new Springy.Graph();

<?php
$nodes_begin="graph.addNodes(";
$nodes_end=");\n";
$edges_begin="graph.addEdges(";
$edges_end=");\n";

$pollip=array('host1.yourdomain.com', 'host2.yourdomain.com', 'host3.yourdomain.com');
$pollsnmpcomm='public';

foreach ($pollip as $thishost){ 
$tcpEntry = snmptable($thishost, $pollsnmpcomm, "1.3.6.1.2.1.6.13.1");

foreach($tcpEntry as $intid => $thisint) {
    # 5 for active connections and remote_port
    if ($thisint[1]==5 && $thisint[5]==3306) {
        # Assign the values to an array with names for easier referencing
        $thisint['localhost'] = $thisint[2];
        $thisint['localport'] = $thisint[3];
        $thisint['remotehost'] = $thisint[4];
        $thisint['remoteport'] = $thisint[5];

	$nodes = $nodes . "'" . $thisint['localhost'] . "'," . "'" . $thisint['remotehost'] . "',";
	$edges = $edges . "'" . $thisint['localhost'] . "', " . "'" . $thisint['remotehost'] . "'],"; 
    }
}
}
print $nodes_begin;
$clean_nodes = rtrim(implode(',',array_unique(explode(',', $nodes))), ",");
print $clean_nodes;
print $nodes_end;

print $edges_begin;
$clean_edges=array_unique(explode('],', $edges));
$clean_edges2=rtrim("[" . str_ireplace(",'", ",['", implode('],',$clean_edges)), ",");
print $clean_edges2;
print $edges_end;

?>
jQuery(function(){
    var springy = jQuery('#springydemo').springy({
        graph: graph
    });
});
</script>
<canvas id="springydemo" width="950" height="600" />
</body>
</html>

<?php
function snmptable($host, $community, $oid) {
     # This handy function was bought to you by scot at indievisible dot org
     # Found on the PHP.net documentation page for snmprealwalk.

     # The important thing about this function is that it fills in the blanks.
     # Regular SNMP walks leave out items so you can't blindly prod things into arrays any more. 

     snmp_set_oid_numeric_print(TRUE);
     snmp_set_quick_print(TRUE);
     snmp_set_enum_print(TRUE);
     snmp_set_valueretrieval(SNMP_VALUE_PLAIN );
     snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

     $retval = array();
     if(!$raw = snmp2_real_walk($host, $community, $oid)) {
         return false;
     }
     if (count($raw) == 0) return false; // no data

     $prefix_length = 0;
     $largest = 0;
     foreach ($raw as $key => $value) {
         if ($prefix_length == 0) {
             // don't just use $oid's length since it may be non-numeric
             $prefix_elements = count(explode('.',$oid));
             $tmp = '.' . strtok($key, '.');
             while ($prefix_elements > 1) {
                 $tmp .= '.' . strtok('.');
                 $prefix_elements--;
             }
             $tmp .= '.';
             $prefix_length = strlen($tmp);
         }
         $key = substr($key, $prefix_length);
         $index = explode('.', $key, 2);
         isset($retval[$index[1]]) or $retval[$index[1]] = array();
         if ($largest < $index[0]) $largest = $index[0];
         $retval[$index[1]][$index[0]] = $value;
     }

     if (count($retval) == 0) return false; // no data

     // fill in holes and blanks the agent may "give" you
     foreach($retval as $k => $x) {
         for ($i = 1; $i <= $largest; $i++) {
         if (! isset($retval[$k][$i])) {
                 $retval[$k][$i] = '';
             }
         }
         ksort($retval[$k]);                                                                                                                                                                                                             
     }
     return($retval);
 }
?>
