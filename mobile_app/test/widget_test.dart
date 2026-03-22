import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_app/src/app.dart';

void main() {
  testWidgets('renderiza tela inicial', (WidgetTester tester) async {
    await tester.pumpWidget(const FullCareMobileApp());

    expect(find.text('FullCare Mobile'), findsOneWidget);
  });
}
