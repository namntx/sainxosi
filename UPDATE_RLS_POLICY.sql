-- Update RLS policy for lottery_results table to allow insert/update from backend
-- This allows the PHP backend to insert/update without authentication

-- Drop existing policies if they exist
DROP POLICY IF EXISTS "Allow all authenticated users" ON lottery_results;
DROP POLICY IF EXISTS "Allow anonymous read" ON lottery_results;
DROP POLICY IF EXISTS "Allow anonymous insert" ON lottery_results;
DROP POLICY IF EXISTS "Allow anonymous update" ON lottery_results;

-- Allow anonymous read (for Flutter app)
CREATE POLICY "Allow anonymous read" ON lottery_results
  FOR SELECT
  TO anon
  USING (true);

-- Allow anonymous insert (for PHP backend)
CREATE POLICY "Allow anonymous insert" ON lottery_results
  FOR INSERT
  TO anon
  WITH CHECK (true);

-- Allow anonymous update (for PHP backend)
CREATE POLICY "Allow anonymous update" ON lottery_results
  FOR UPDATE
  TO anon
  USING (true)
  WITH CHECK (true);

-- Optional: Allow authenticated users full access
CREATE POLICY "Allow authenticated users" ON lottery_results
  FOR ALL
  TO authenticated
  USING (true)
  WITH CHECK (true);

