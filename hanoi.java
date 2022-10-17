//Prabhutva contibution on hanoi puzzle

class pb
{
	static void towerOfHanoi(int i, char from_rod, char to_rod, char aux_rod)
	{
		if (i == 1)
		{
			System.out.println("Move disk 1 from rod " + from_rod + " to rod " + to_rod);
			return;
		}
		towerOfHanoi(i-1, from_rod, aux_rod, to_rod);
		System.out.println("Move disk " + i + " from rod " + from_rod + " to rod " + to_rod);
		towerOfHanoi(i-1, aux_rod, to_rod, from_rod);
	}
	
	
	public static void main(String args[])
	{
		int i = 4; // Number of disks
		towerOfHanoi(i, \'A\', \'C\', \'B\'); // A, B and C are names of rods
	}
}
