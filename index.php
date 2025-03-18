<?php

class ChessGame {
    private $board;
    private $turn;
    private $history;

    public function __construct() {
        $this->initializeBoard();
        $this->turn = 'w'; // Putih mulai lebih dulu
        $this->history = [];
    }

    private function initializeBoard() {
        $this->board = [
            ['R', 'N', 'B', 'Q', 'K', 'B', 'N', 'R'],
            ['P', 'P', 'P', 'P', 'P', 'P', 'P', 'P'],
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
            [' ', ' ', ' ', ' ', ' ', ' ', ' ', ' '],
            ['p', 'p', 'p', 'p', 'p', 'p', 'p', 'p'],
            ['r', 'n', 'b', 'q', 'k', 'b', 'n', 'r']
        ];
    }

    public function displayBoard() {
        echo "  a b c d e f g h\n";
        echo " +----------------\n";
        for ($i = 0; $i < 8; $i++) {
            echo (8 - $i) . "|";
            for ($j = 0; $j < 8; $j++) {
                echo $this->board[$i][$j] . ' ';
            }
            echo "|\n";
        }
        echo " +----------------\n";
    }

    public function movePiece($from, $to) {
        list($fromX, $fromY) = $this->parsePosition($from);
        list($toX, $toY) = $this->parsePosition($to);

        if ($this->isValidMove($fromX, $fromY, $toX, $toY)) {
            $this->history[] = $this->board;
            $temp = $this->board[$toX][$toY];
            $this->board[$toX][$toY] = $this->board[$fromX][$fromY];
            $this->board[$fromX][$fromY] = ' ';

            if ($this->isCheck($this->turn)) {
                echo "Move puts own king in check! Reverting...\n";
                $this->undoMove();
                return;
            }

            $this->turn = ($this->turn === 'w') ? 'b' : 'w';

            if ($this->isCheck($this->turn)) {
                echo "Check!\n";
                if ($this->isCheckmate($this->turn)) {
                    echo "Checkmate! Game over.\n";
                    exit;
                }
            }
        } else {
            echo "Invalid move! Try again.\n";
        }
    }

    public function undoMove() {
        if (!empty($this->history)) {
            $this->board = array_pop($this->history);
            $this->turn = ($this->turn === 'w') ? 'b' : 'w';
            echo "Undo successful.\n";
        } else {
            echo "No moves to undo!\n";
        }
    }

    private function parsePosition($pos) {
        $letters = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5, 'g' => 6, 'h' => 7];
        $x = 8 - intval($pos[1]);
        $y = $letters[$pos[0]];
        return [$x, $y];
    }
}

$game = new ChessGame();
while (true) {
    $game->displayBoard();
    echo "Enter your move (e.g., e2 e4) or 'undo': ";
    $input = trim(fgets(STDIN));
    if ($input === 'exit') break;
    if ($input === 'undo') {
        $game->undoMove();
    } else {
        $moves = explode(' ', $input);
        if (count($moves) == 2) {
            $game->movePiece($moves[0], $moves[1]);
        } else {
            echo "Invalid input. Please use format: e2 e4 or 'undo'\n";
        }
    }
}
