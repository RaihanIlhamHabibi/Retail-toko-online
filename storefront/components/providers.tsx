"use client";

import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import { apiRequest, type Product } from "@/lib/api";

type User = { id: number; name: string; email: string; role: string };
type CartLine = { product: Product; quantity: number };
type AuthContextValue = {
  token: string | null;
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, password_confirmation: string) => Promise<void>;
  logout: () => Promise<void>;
};
type CartContextValue = {
  lines: CartLine[];
  count: number;
  add: (product: Product) => void;
  remove: (productId: number) => void;
  setQuantity: (productId: number, quantity: number) => void;
  clear: () => void;
};

const AuthContext = createContext<AuthContextValue | null>(null);
const CartContext = createContext<CartContextValue | null>(null);

export function Providers({ children }: { children: ReactNode }) {
  const [token, setToken] = useState<string | null>(null);
  const [user, setUser] = useState<User | null>(null);
  const [lines, setLines] = useState<CartLine[]>([]);

  const login = useCallback(async (email: string, password: string) => {
    const result = await apiRequest<{ access_token: string; user: User }>(
      "/auth/login",
      { method: "POST", body: JSON.stringify({ email, password }) },
    );
    setToken(result.access_token);
    setUser(result.user);
  }, []);

  const register = useCallback(async (
    name: string,
    email: string,
    password: string,
    password_confirmation: string,
  ) => {
    const result = await apiRequest<{ access_token: string; user: User }>(
      "/auth/register",
      {
        method: "POST",
        body: JSON.stringify({ name, email, password, password_confirmation }),
      },
    );
    setToken(result.access_token);
    setUser(result.user);
  }, []);

  const logout = useCallback(async () => {
    if (token) {
      await apiRequest("/auth/logout", { method: "POST" }, token);
    }
    setToken(null);
    setUser(null);
  }, [token]);

  const add = useCallback((product: Product) => {
    setLines((current) => {
      const found = current.find((line) => line.product.id === product.id);
      if (found) {
        return current.map((line) =>
          line.product.id === product.id
            ? {
                ...line,
                quantity: Math.min(line.quantity + 1, product.stock_quantity),
              }
            : line,
        );
      }
      return [...current, { product, quantity: 1 }];
    });
  }, []);

  const remove = useCallback((productId: number) => {
    setLines((current) =>
      current.filter((line) => line.product.id !== productId),
    );
  }, []);

  const setQuantity = useCallback((productId: number, quantity: number) => {
    setLines((current) =>
      current.map((line) =>
        line.product.id === productId
          ? {
              ...line,
              quantity: Math.max(
                1,
                Math.min(quantity, line.product.stock_quantity),
              ),
            }
          : line,
      ),
    );
  }, []);

  const authValue = useMemo(
    () => ({ token, user, login, register, logout }),
    [token, user, login, register, logout],
  );
  const cartValue = useMemo(
    () => ({
      lines,
      count: lines.reduce((total, line) => total + line.quantity, 0),
      add,
      remove,
      setQuantity,
      clear: () => setLines([]),
    }),
    [lines, add, remove, setQuantity],
  );

  return (
    <AuthContext.Provider value={authValue}>
      <CartContext.Provider value={cartValue}>{children}</CartContext.Provider>
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const value = useContext(AuthContext);
  if (!value) throw new Error("useAuth must be used inside Providers.");
  return value;
}

export function useCart() {
  const value = useContext(CartContext);
  if (!value) throw new Error("useCart must be used inside Providers.");
  return value;
}
