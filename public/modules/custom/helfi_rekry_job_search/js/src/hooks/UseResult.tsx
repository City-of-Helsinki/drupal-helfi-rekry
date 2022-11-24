import { useEffect, useState } from 'react';

const UseResult = (query: any) => {
  const [result, setResult] = useState<any>({});
  const [loading, setLoading] = useState<boolean>(false);

  useEffect(() => {
    const getResults = async () => {
      const proxyUrl = drupalSettings?.helfi_rekry_job_search?.elastic_proxy_url;
      const url: string | undefined = proxyUrl || process.env.REACT_APP_ELASTIC_URL;

      if (!url) {
        console.warn('Proxy URL not set!');
        return;
      }

      setLoading(true);
      const result = await fetch(`${url}/_search`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(query),
      });

      const json = await result.json();

      setLoading(false);

      if (json) {
        setResult(json);
      }
    };

    getResults();
  }, [query]);

  return {
    isLoading: loading,
    ...result,
  };
};

export default UseResult;
