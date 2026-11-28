import {useState} from 'react';
import {Text, TextInput} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';

import { couleurs, styles } from './ConnexionView.styles';

export default function ConnexionView() {
    const [email, setEmail] = useState('');
    return (
        <SafeAreaView style={styles.ecran}>
            <Text style={styles.marque}>LE CLAVIER D’OR</Text>
            <Text style={styles.titre}>Content de te revoir</Text>
            <Text style={styles.sousTitre}>
                Connecte-toi pour reprendre ta progression.
            </Text>
            <Text style={styles.libelle}>Adresse e-mail</Text>
            <TextInput
                style={styles.champ}
                accessibilityLabel="Adresse e-mail"
                placeholder="nom@exemple.fr"
                placeholderTextColor={couleurs.secondaire}
                keyboardType="email-address"
                autoCapitalize="none"
                value={email}
                onChangeText={setEmail}
            />
        </SafeAreaView>
    );
}