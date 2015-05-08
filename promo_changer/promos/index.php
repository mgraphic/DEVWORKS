<?php
  
  if (!file_exists('promos.xml')) die('[]');
  
  $xml = simplexml_load_file('promos.xml');
  
  $rows = $xml->xpath('ss:Worksheet/ss:Table/ss:Row');
  
  if (!$rows) die('[]');
  
  $data = array();
  
  foreach ($rows AS $key => $row)
  {
      if ($key == 0 OR !$row->Cell) continue;
      
      $include = true;
      
      $current = array();
      
      $column = 'A';
      
      foreach ($row->Cell AS $cell)
      {
          if (!$include) continue;
          
          switch ($column++)
          {
              case 'A':
                  // IMAGE (Required)
                  if (empty($cell->Data)) $include = false;
                  
                  $current['img'] = trim($cell->Data);
              break;
              case 'B':
                  // LINK (Optional)
                  $current['url'] = trim($cell->Data);
              break;
              case 'C':
                  // TITLE (Required)
                  if (empty($cell->Data)) $include = false;
                  
                  $current['title'] = htmlentities(trim($cell->Data));
              break;
              case 'D':
                  // IMAGE STATUS
                  if (strtolower(trim($cell->Data)) != 'enabled') $include = false;
              break;
          }
      }
      
      if ($include) $data[] = $current;
  }
  
  echo json_encode($data);
  
