<?php // setting up a snake

// to save array as uri param --
// https://stackoverflow.com/questions/1763508/passing-arrays-as-url-parameter

define("SNAKE_BODY_PARAM_NAME", "b");

function query($key, $default) {
  $maybeValue = $_GET[$key];
  if(empty($maybeValue)) {
    return $default;
  }
  return $maybeValue;
}

function pos_eq($pos1, $pos2) {
  list($a1, $b1) = $pos1;
  list($a2, $b2) = $pos2;
  // had to switch off strict comparison
  return ($a1 == $a2) && ($b1 == $b2);
}

$BOARD_SIZE = (int)query("board_size", 10);

function start_body() {
  global $BOARD_SIZE;
  $middle = intdiv($BOARD_SIZE, 2);
  return array( $middle, $middle );
}

$SNAKE_BODY = (array)query(SNAKE_BODY_PARAM_NAME, array( start_body() )); 

function positions_contain($positions, $testing) {
  foreach($positions as $pos) {
    if(pos_eq($testing, $pos)) {
      return true;
    }
  }
  return false;
}

function find_food_pos($excluding) {
  global $BOARD_SIZE;
  $bound = $BOARD_SIZE - 1;
  while(true) { // just a fast and bad solution
    $rnd = array( rand(0, $bound), rand(0, $bound) );
    if(!positions_contain($excluding, $rnd)) {
      return $rnd;
    }
  }
}

$FOOD_POS = (array)query("food_pos", find_food_pos($SNAKE_BODY));

function is_food_pos($pos) {
  global $FOOD_POS;
  return pos_eq($pos, $FOOD_POS);
}

function normalize($val) {
  global $BOARD_SIZE;
  $bound = $BOARD_SIZE - 1;
  if($val > $bound) {
    return 0;
  }
  if($val < 0) {
    return $bound;
  }
  return $val;
}

function normalize_pos($mr, $mc) {
  return array( normalize($mr), normalize($mc) );
}

function neighbors($pos) {
  $moves = [[1, 0], [0, 1], [-1, 0], [0, -1]];
  $output = array();
  list($row, $col) = $pos;
  foreach($moves as list($ir, $ic)) {
    $output[] = normalize_pos($row + $ir, $col + $ic);
  }
  return $output;
}

function next_steps($pos) {
  return array_filter(neighbors($pos), function($maybe) {
    global $SNAKE_BODY;
    return !positions_contain($SNAKE_BODY, $maybe);
  });
}

$SNAKE_HEAD = end($SNAKE_BODY);
$NEXT_STEPS = next_steps($SNAKE_HEAD);

function is_next($pos) {
  global $NEXT_STEPS;
  foreach($NEXT_STEPS as $step) {
    if(pos_eq($pos, $step)) {
      return true;
    }
  }
  return false;
}

// now its time to render some html ?>

<!DOCTYPE html>
<html lang="en">
<head>

  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Snake Game!</title>

  <style>
    * {
      box-sizing: border-box;
      padding: 0;
      margin: 0;
    }

    :root {
      --board-size: <?= $BOARD_SIZE ?>;
    }

    .outer-container {
      height: 100vh;
      background-color: #f5f5f5;
      display: grid;
      place-items: center;
    }

    .inner-container {
      background-color: white;
      border-radius: 7px;
      padding: 2em;
      box-shadow: grey 1px 1px 4px;
    }

    .snake-board {
      display: grid;
      grid-template-rows: repeat(var(--board-size), 25px);
      grid-template-columns: repeat(var(--board-size), 25px);
    }

    .snake-board .empty {
      background-color: black;
    }

    .snake-board .snake {
      background-color: red;
    }

    .snake-board .head {
      background-color: orange;
    }

    .snake-board .neighbor {
      background-color: #2e1919;
    }

    .snake-board .food {
      background-color: green;
    }

    .snake-board .neighbor:hover {
      background-color: yellow;
    }
  </style>

</head>
<body>

<div class="outer-container">
  <div class="inner-container">
    <div class="snake-board">
<?php // rendering snake board

for($row = 0; $row < $BOARD_SIZE; $row++) {
  for($column = 0; $column < $BOARD_SIZE; $column++) {

    $pos = array( $row, $column );
    $celltype = match (true) {
      pos_eq($SNAKE_HEAD, $pos) => "head",
      positions_contain($SNAKE_BODY, $pos) => "snake",
      is_food_pos($pos) => "food",
      default => "empty",
    };

    if(is_next($pos)) {

      // to do later
      if(is_food_pos($pos)) {
        $next_snake_body = [...$SNAKE_BODY, $pos];
        $next_food_pos = find_food_pos($next_snake_body);
      } else {
        $next_snake_body = [...array_slice($SNAKE_BODY,1), $pos];
        $next_food_pos = $FOOD_POS;
      }

      $query = http_build_query(array(
        "board_size" => $BOARD_SIZE,
        SNAKE_BODY_PARAM_NAME => $next_snake_body,
        "food_pos" => $next_food_pos,
      ));

      $uri = $_SERVER["PHP_SELF"] . "?" . $query;

      echo "<a href=\"$uri\" class=\"$celltype neighbor\"></a>";

    } else {
      echo "<div class=\"$celltype\"></div>";
    }

  }
}

?>
    </div>
<?php // if there are no futher steps, -- GAME OVER

if (count($NEXT_STEPS) == 0) {
  $again_uri = $_SERVER["PHP_SELF"];
  echo <<< HTML
  <div>
    <h1>GAME OVER!</h1>
    <a href="$again_uri">Again!</a>
  </div>
  HTML;
}

?>
  </div>
</div>

</body>
</html>