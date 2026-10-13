import { useCallback, useEffect, useState } from 'react';
import { api } from '../api/client';

type ApiKeyRow = {
  id: number;
  prefix: string;
  scopes: string[];
  created_at: string | null;
  last_used_at: string | null;
  revoked: boolean;
};

type Usage = {
  billable_calls: number;
  quota: number;
  remaining: number;
  resets_at: string;
};

export default function DeveloperPage() {
  const [keys, setKeys] = useState<ApiKeyRow[]>([]);
  const [usage, setUsage] = useState<Usage | null>(null);
  const [newSecret, setNewSecret] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [mcpPing, setMcpPing] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [keysRes, usageRes] = await Promise.all([
        api<{ data: ApiKeyRow[] }>('/keys'),
        api<{ data: Usage }>('/usage'),
      ]);
      setKeys(keysRes.data);
      setUsage(usageRes.data);
    } catch (e: unknown) {
      setError(JSON.stringify(e));
    }
  }, []);

  useEffect(() => {
    void load();
  }, [load]);

  async function createKey() {
    setNewSecret(null);
    try {
      const res = await api<{ data: { secret: string } }>('/keys', {
        method: 'POST',
        body: JSON.stringify({ scopes: ['search:read'] }),
      });
      setNewSecret(res.data.secret);
      await load();
    } catch (e: unknown) {
      setError(JSON.stringify(e));
    }
  }

  async function revoke(id: number) {
    await api(`/keys/${id}`, { method: 'DELETE' });
    await load();
  }

  function copyMcpConfig() {
    const token = import.meta.env.VITE_MCP_API_KEY ?? import.meta.env.VITE_MANAGE_API_KEY ?? '<your-search-key>';
    const config = {
      mcpServers: {
        'gethookd-ads': {
          command: 'docker',
          args: [
            'compose',
            '--profile',
            'mcp',
            'run',
            '--rm',
            '-i',
            'mcp',
          ],
          env: {
            LARAVEL_API_URL: 'http://backend:8000/api/v1',
            LARAVEL_API_TOKEN: token,
          },
        },
      },
    };
    void navigator.clipboard.writeText(JSON.stringify(config, null, 2));
  }

  async function checkMcp() {
    try {
      const searchKey = import.meta.env.VITE_MCP_API_KEY ?? '';
      const res = await fetch(`${import.meta.env.VITE_API_URL}/mcp/ping`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${searchKey}`,
          Accept: 'application/json',
        },
      });
      const body = await res.json();
      setMcpPing(res.ok ? `Received: ${body.data?.received_at}` : JSON.stringify(body));
    } catch (e: unknown) {
      setMcpPing(String(e));
    }
  }

  return (
    <main style={{ fontFamily: 'system-ui', padding: '1.5rem', maxWidth: 720 }}>
      <h1>Developer</h1>
      <p>Local demo user — set <code>VITE_MANAGE_API_KEY</code> to the seeded manage key.</p>
      {error && <pre style={{ color: 'crimson' }}>{error}</pre>}

      <section>
        <h2>Usage</h2>
        {usage && (
          <p>
            {usage.billable_calls} / {usage.quota} billable calls (resets {usage.resets_at})
          </p>
        )}
      </section>

      <section>
        <h2>API keys</h2>
        <button type="button" onClick={() => void createKey()}>Create key</button>
        {newSecret && (
          <p>
            Secret (copy now): <code>{newSecret}</code>{' '}
            <button type="button" onClick={() => void navigator.clipboard.writeText(newSecret)}>Copy</button>
          </p>
        )}
        <ul>
          {keys.map((k) => (
            <li key={k.id}>
              {k.prefix}… — {k.scopes.join(', ')} — {k.revoked ? 'revoked' : 'active'}
              {!k.revoked && (
                <button type="button" onClick={() => void revoke(k.id)} style={{ marginLeft: 8 }}>Revoke</button>
              )}
            </li>
          ))}
        </ul>
      </section>

      <section>
        <h2>Connect your AI tool</h2>
        <button type="button" onClick={copyMcpConfig}>Copy MCP config</button>
        <button type="button" onClick={() => void checkMcp()} style={{ marginLeft: 8 }}>Check MCP ping</button>
        {mcpPing && <p>{mcpPing}</p>}
      </section>
    </main>
  );
}
