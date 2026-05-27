export type UserRole = 'admin' | 'expert' | 'contributor';

export interface User {
  id: string;
  username: string;
  password?: string;
  name: string;
  role: UserRole;
}

export interface Department {
  id: string;
  name: string;
  iconName: string; // Used to pick appropriate Lucide icon dynamically
}

export interface Category {
  id: string;
  name: string;
  departmentId: string;
}

export interface KnowledgeEntry {
  id: number;
  title: string;
  categoryId: string;
  categoryName: string;
  problem: string;
  solution: string;
  result?: string;
  keywords: string[];
  mediaUrl?: string;
  mediaType?: 'image' | 'video' | 'audio' | '';
  author: string;
  authorRole: UserRole;
  dateOccurred: string;
  views: number;
  status: 'approved' | 'draft' | 'rejected';
  rejectionReason?: string;
  departmentId: string;
}

export interface Answer {
  id: number;
  answerText: string;
  replierName: string;
  replierRole: string;
  createdAt: string;
  isAccepted: boolean;
}

export interface Question {
  id: number;
  title: string;
  questionText: string;
  priority: 'normal' | 'urgent' | 'critical';
  status: 'open' | 'resolved';
  author: string;
  createdAt: string;
  userId: string;
  departmentId: string;
  answers: Answer[];
}

export interface VisualTip {
  id: number;
  title: string;
  description: string;
  imageUrl?: string;
  departmentId: string;
}
